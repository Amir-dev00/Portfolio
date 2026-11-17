#!/usr/bin/env python3
"""Plan production changes from Git blob IDs without reading unchanged files."""

import argparse
import filecmp
import json
from pathlib import Path, PurePosixPath
import re
import shutil
import subprocess

APP_DIRS = ('app/', 'config/', 'resources/', 'routes/', 'lang/', 'database/migrations/')
APP_FILES = {
    'bootstrap/app.php', 'bootstrap/providers.php', 'composer.json', 'composer.lock',
}
PUBLIC_DIRS = ('assets/', 'css/', 'js/')
PUBLIC_FILES = {'index.php', 'favicon.ico'}
COMPOSER_FILES = ('composer.json', 'composer.lock')


def is_managed(path):
    parts = PurePosixPath(path).parts
    if len(parts) < 2 or not re.fullmatch(r'[A-Za-z0-9_./ -]+', path):
        return False
    if any(part.startswith('.') for part in path.split('/')) or path.endswith(('.sql', '.log')):
        return False
    if parts[0] == 'portfolio-app':
        name = '/'.join(parts[1:])
        return name in APP_FILES or name.startswith(APP_DIRS)
    if parts[0] == 'public_html':
        name = '/'.join(parts[1:])
        return name in PUBLIC_FILES or name.startswith(PUBLIC_DIRS)
    return False


def target_path(source):
    if source == 'cms/public/index.cpanel.php':
        return 'public_html/index.php', 0
    if source.startswith('cms/public/'):
        name = source.removeprefix('cms/public/')
        if name.startswith(PUBLIC_DIRS) or name == 'favicon.ico':
            return f'public_html/{name}', 0
    if source.startswith(PUBLIC_DIRS):
        # Keep the root asset override used by the original cPanel package.
        return f'public_html/{source}', 1
    if source.startswith('cms/'):
        return 'portfolio-app/' + source.removeprefix('cms/'), 0
    return '', 0


def git_files(root):
    result = subprocess.check_output(['git', 'ls-tree', '-r', '-z', 'HEAD'], cwd=root)
    files = {}
    for entry in result.decode().split('\0'):
        if not entry:
            continue
        metadata, source = entry.split('\t', 1)
        mode, _, blob = metadata.split()
        target, priority = target_path(source)
        if not is_managed(target):
            continue
        if mode not in ('100644', '100755'):
            raise ValueError(f'Deployment path must be a regular Git file: {source}')
        if target not in files or priority > files[target][2]:
            files[target] = (blob, source, priority)
    return files


def read_state(path):
    if not path.exists():
        return {'applied': None, 'pending': [], 'vendor_pending': False}
    state = json.loads(path.read_text(encoding='utf-8'))
    applied = state.get('applied')
    pending = state.get('pending')
    if (
        state.get('version') != 1
        or (applied is not None and not isinstance(applied, dict))
        or not isinstance(pending, list)
        or not isinstance(state.get('vendor_pending'), bool)
    ):
        raise ValueError('Invalid deployment state format')
    for name in [*(applied or {}), *pending]:
        if not isinstance(name, str) or not is_managed(name) or '//' in name:
            raise ValueError('Deployment state contains a path outside the managed file set')
    if applied is not None and any(
        not isinstance(blob, str) or not re.fullmatch(r'[0-9a-f]{40,64}', blob)
        for blob in applied.values()
    ):
        raise ValueError('Deployment state contains an invalid Git blob ID')
    return state


def write_state(path, applied, pending, vendor_pending):
    path.write_text(json.dumps({
        'version': 1,
        'applied': applied,
        'pending': sorted(pending),
        'vendor_pending': vendor_pending,
    }, sort_keys=True, separators=(',', ':')) + '\n', encoding='utf-8')


def plan(root, stage):
    sources = git_files(root)
    wanted = {name: details[0] for name, details in sources.items()}
    state = read_state(stage / 'remote-state.json')
    applied = state['applied']
    previously_touched = set(state['pending'])
    before = applied or {}
    upload = {name for name, blob in wanted.items()
              if before.get(name) != blob or name in previously_touched}
    remove = (set(before) | previously_touched) - set(wanted)
    composer_changes = any(
        before.get(f'portfolio-app/{name}') != wanted.get(f'portfolio-app/{name}')
        for name in COMPOSER_FILES
    )
    if applied is None:
        # The site's existing locked vendor tree can be reused on the first run.
        composer_changes = not all(
            (stage / f'live-{name}').is_file()
            and filecmp.cmp(stage / f'live-{name}', root / 'cms' / name, shallow=False)
            for name in COMPOSER_FILES
        )
    vendor = state['vendor_pending'] or composer_changes or any(name.startswith('portfolio-app/app/') for name in remove)

    for name in sorted(upload):
        target = stage / 'files' / name
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copyfile(root / sources[name][1], target)

    # Commands contain only checked, managed file paths. Removals never recurse.
    (stage / 'delete.lftp').write_text(
        ''.join(f'rm -f "/{name}"\n' for name in sorted(remove)), encoding='utf-8'
    )
    write_state(stage / 'pending.json', applied, upload | remove, vendor)
    write_state(stage / 'complete.json', wanted, [], False)

    flags = {
        'changed': bool(upload or remove or vendor),
        'vendor': vendor,
        'app': vendor or any(name.startswith('portfolio-app/') for name in upload | remove),
    }
    print(f'{"Initial sync" if applied is None else "Incremental sync"}: '
          f'{len(upload)} upload(s), {len(remove)} removal(s); vendor rebuild: {vendor}.')
    return flags


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('stage', type=Path)
    parser.add_argument('github_output', type=Path)
    args = parser.parse_args()
    settings = plan(Path(__file__).resolve().parents[1], args.stage)
    with args.github_output.open('a', encoding='utf-8') as output:
        for key, value in settings.items():
            output.write(f'{key}={str(value).lower()}\n')
