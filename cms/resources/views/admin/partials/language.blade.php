<form class="admin-language" method="post" action="{{ route('admin.language') }}" aria-label="{{ __('Admin language') }}">
  @csrf
  <button type="submit" class="btn btn-secondary" name="locale" value="fa" lang="fa" @if(app()->isLocale('fa')) aria-current="true" @endif>فارسی</button>
  <button type="submit" class="btn btn-secondary" name="locale" value="en" lang="en" @if(app()->isLocale('en')) aria-current="true" @endif>English</button>
</form>
