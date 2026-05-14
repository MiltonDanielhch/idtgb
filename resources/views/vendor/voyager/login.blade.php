@extends('voyager::auth.master')

@section('content')
    <div class="login-container">

        <p>{{ __('voyager::login.signin_below') }}</p>

        <form action="{{ route('voyager.login') }}" method="POST">
            {{ csrf_field() }}
            <div class="form-group form-group-default" id="emailGroup">
                <label>{{ __('voyager::generic.email') }}</label>
                <div class="controls">
                    <input type="text" name="email" id="email" value="{{ old('email') }}" placeholder="{{ __('voyager::generic.email') }}" class="form-control" required>
                </div>
            </div>

            {{-- <div class="form-group form-group-default" id="passwordGroup">
                <label>{{ __('voyager::generic.password') }}</label>
                <div class="controls">
                    <input type="password" name="password" placeholder="{{ __('voyager::generic.password') }}" class="form-control" required>
                </div>
            </div> --}}
            <div class="form-group form-group-default" id="passwordGroup">
                <label>{{ __('voyager::generic.password') }}</label>
                <div class="input-group controls">
                    <input type="password" id="input-password" name="password" placeholder="{{ __('voyager::generic.password') }}" class="form-control" required>
                    <span class="input-group-addon" style="background:#fff;border:0px;font-size:25px;cursor:pointer;padding:0px;position: relative;bottom:10px" id="btn-verpassword">
                        <span class="fa fa-eye"></span>
                    </span>
                </div>
            </div>


            
            <div class="form-group" id="rememberMeGroup">
                <div class="controls">
                    <input type="checkbox" name="remember" id="remember" value="1"><label for="remember" class="remember-me-text">{{ __('voyager::generic.remember_me') }}</label>
                </div>
            </div>

            <button type="submit" class="btn btn-block login-button">
                <span class="signingin hidden"><span class="voyager-refresh"></span> {{ __('voyager::login.loggingin') }}...</span>
                <span class="signin">{{ __('voyager::generic.login') }}</span>
            </button>

        </form>

        <div style="clear:both"></div>

        @if(!$errors->isEmpty())
            <div class="alert alert-red">
                <ul class="list-unstyled">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

    </div> <!-- .login-container -->
@endsection

@section('post_js')
    <script type="text/javascript" src="{{ voyager_asset('js/app.js') }}"></script>
    <script>
        var btn = document.querySelector('button[type="submit"]');
        var form = document.forms[0];
        var email = document.querySelector('[name="email"]');
        var password = document.querySelector('[name="password"]');
        btn.addEventListener('click', function(ev){
            if (form.checkValidity()) {
                btn.querySelector('.signingin').className = 'signingin';
                btn.querySelector('.signin').className = 'signin hidden';
            } else {
                ev.preventDefault();
            }
        });
        email.focus();
        document.getElementById('emailGroup').classList.add("focused");

        // Focus events for email and password fields
        email.addEventListener('focusin', function(e){
            document.getElementById('emailGroup').classList.add("focused");
        });
        email.addEventListener('focusout', function(e){
            document.getElementById('emailGroup').classList.remove("focused");
        });

        password.addEventListener('focusin', function(e){
            document.getElementById('passwordGroup').classList.add("focused");
        });
        password.addEventListener('focusout', function(e){
            document.getElementById('passwordGroup').classList.remove("focused");
        });

        $(document).ready(function(){
            let ver_pass = false;
            $('#btn-verpassword').click(function(){
                if(ver_pass){
                    ver_pass = false;
                    $(this).html('<span class="fa fa-eye"></span>');
                    $('#input-password').prop('type', 'password');
                }else{
                    ver_pass = true;
                    $(this).html('<span class="fa fa-eye-slash"></span>');
                    $('#input-password').prop('type', 'text');
                }
            });
        });

    </script>


@if (setting('configuracion.navidad'))
    <script type="text/javascript" src="{{asset('navidad/snow.js')}}"></script>
    <script type="text/javascript">
        $(function() {
            $(document).snow({ SnowImage: "{{ asset('navidad/image/icon.png') }}", SnowImage2: "{{ asset('navidad/image/caramelo.png') }}" });
        });
    </script>
@endif
@endsection
