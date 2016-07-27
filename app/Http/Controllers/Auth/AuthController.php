<?php

namespace App\Http\Controllers\Auth;
use Illuminate\contracts\Auth\Guard;
use App\User;
use Validator;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Auth;
class AuthController extends Controller
{


    use AuthenticatesAndRegistersUsers, ThrottlesLogins;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'logout']);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|confirmed|min:6',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);
    }

    public function getSocialRedirect( $provider )
    {
        $providerKey = \Config::get('services.' . $provider);
        if(empty($providerKey))
            return view('pages.status')
                ->with('error','No such provider');

        return Socialite::driver( $provider )->redirect();

    }

    public function getSocialHandle( $provider )
    {

        $user = Socialite::driver( $provider )->user();

        $code = Input::get('code');
        if(!$code)
            return redirect()->route('auth.login')
                ->with('status', 'danger')
                ->with('message', 'You did not share your profile data with our social app.');

        if(!$user->email)
        {
            return redirect()->route('auth.login')
                ->with('status', 'danger')
                ->with('message', 'You did not share your email with our social app. You need to visit App Settings and remove our app, than you can come back here and login again. Or you can create new account.');
        }

        $socialUser = null;

        //Check is this email present
        $userCheck = User::where('email', '=', $user->email)->first();
        if(!empty($userCheck))
        {
            $socialUser = $userCheck;
        }
        else
        {
            $sameSocialId = Social::where('social_id', '=', $user->id)->where('provider', '=', $provider )->first();

            if(empty($sameSocialId))
            {
                //There is no combination of this social id and provider, so create new one
                $newSocialUser = new User;
                $newSocialUser->email              = $user->email;
                $name = explode(' ', $user->name);
                $newSocialUser->first_name         = $name[0];
                $newSocialUser->last_name          = $name[1];
                $newSocialUser->save();

                $socialData = new Social;
                $socialData->social_id = $user->id;
                $socialData->provider= $provider;
                $newSocialUser->social()->save($socialData);

                // Add role
               // $role = Role::whereName('user')->first();
                //$newSocialUser->assignRole($role);

                $socialUser = $newSocialUser;
            }
            else
            {
                //Load this existing social user
                $socialUser = $sameSocialId->user;
            }

        }

        $this->auth->login($socialUser, true);
       // $this->auth



        return \App::abort(500);
    }


    public function upload(Request $request)
    {

        $errorcode="NA";
        $status="uploaded";
        if( !$request->hasFile('fileToUpload'))
        {
            $errorcode="file not found";
            $status="failed";
            $values=array('errorcode'=>$errorcode,'status'=>$status);
            return (json_encode($values));
            //return $request->file('fileToUpload')->getClientOriginalName();
            //return 'true';
        }
        $size = Input::file('fileToUpload')->getSize();
        $filename=$request->file('fileToUpload')->getClientOriginalName();
        $fileext=$request->file('fileToUpload')->getClientOriginalExtension();
        $destination='Downloads';
        if(!$request->file('fileToUpload')->isValid())
        {
            $errorcode="incomplete file";
            $status="failed";
            $values=array('errorcode'=>$errorcode,'status'=>$status,array('filename'=>$filename,'fileext'=>$fileext,'filesize'=>$size));
            return (json_encode($values));
        }
        if($fileext=="php" || $fileext=="html")
        {
            $errorcode="Invalid file format";
            $status="failed";
            $values=array('errorcode'=>$errorcode,'status'=>$status,array('filename'=>$filename,'fileext'=>$fileext,'filesize'=>$size));
            return (json_encode($values));

        }
        if($size>1500)
        {
            $errorcode="choose a smaller file";
            $status="failed";
            $values=array('errorcode'=>$errorcode,'status'=>$status,array('filename'=>$filename,'fileext'=>$fileext,'filesize'=>$size));
            return (json_encode($values));

        }
        if(($request->file('fileToUpload')->move($destination,$filename)))
        {

            $values=array('errorcode'=>$errorcode,'status'=>$status,'data'=>array('filename'=>$filename,'fileext'=>$fileext,'filesize'=>$size));
            return (json_encode($values));
            //return $values;
        }
        $errorcode="failed to upload";
        $status="Failed";
        $values=array('errorcode'=>$errorcode,'status'=>$status,array('filename'=>$filename,'fileext'=>$fileext,'filesize'=>$size));
        return (json_encode($values));
    }
}
