<details class="project-delete">
  <summary>{{ __('Delete project') }}</summary>
  <form method="post" action="{{ route('admin.projects.destroy', $project) }}">
    @csrf
    @method('DELETE')
    <p class="admin-hint">{{ __('This removes the draft project and its SEO settings. The public site changes when you publish.') }}</p>
    <label><input name="confirm_delete" type="checkbox" value="1" required> {{ __('Confirm project deletion') }}</label>
    <button type="submit" class="btn-danger">{{ __('Delete project') }}</button>
  </form>
</details>
