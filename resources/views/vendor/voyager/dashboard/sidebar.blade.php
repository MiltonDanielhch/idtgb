<div class="side-menu sidebar-inverse">
    <nav class="navbar navbar-default" role="navigation">
        <div class="side-menu-container">
            <div class="navbar-header" style="background-color: #28467e">
                <a class="navbar-brand" href="{{ route('voyager.dashboard') }}">
                    <div class="logo-icon-container">
                        <?php $admin_logo_img = Voyager::setting('admin.icon_image', ''); ?>
                        @if($admin_logo_img == '')
                            <img src="{{ voyager_asset('images/logo-icon-light.png') }}" alt="Logo Icon">
                        @else
                            <img src="{{ Voyager::image($admin_logo_img) }}" alt="Logo Icon">
                        @endif
                    </div>
                    <div class="title">{{Voyager::setting('admin.title', 'VOYAGER')}}</div>
                </a>
            </div><!-- .navbar-header -->

            <div class="panel widget center bgimage"
                 style="background-image:url({{ Voyager::image( Voyager::setting('admin.bg_image'), voyager_asset('images/bg.jpg') ) }}); background-size: cover; background-position: 0px;">
                @if(Auth::check())
                <div class="dimmer"></div>
                <div class="panel-content">
                    @php
                        $user = Auth::user();
                        $user_avatar = Voyager::image($user->avatar);
                        if ($user->person && $user->person->image) {
                            $user_avatar = asset('storage/'.str_replace('.', '-cropped.', $user->person->image));
                        }
                    @endphp
                    <img src="{{ $user_avatar }}" class="avatar" alt="{{ $user->name }} avatar">
                    <h4 style="color:rgb(255, 255, 255) !important">{{ ucwords($user->name) }}</h4>
                    <p>{{ $user->email }}</p>

                    <a href="{{ route('voyager.profile') }}" class="btn btn-primary">{{ __('voyager::generic.profile') }}</a>
                    <div style="clear:both"></div>
                </div>
                @endif
            </div>

        </div>
       <div id="adminmenu">
            <admin-menu :items="{{ Auth::check() ? menu('admin', '_json') : '[]' }}"></admin-menu>
        </div>
    </nav>
</div>
