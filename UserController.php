<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use App\Models\RoleUser;
use Activation;
use Reminder;
use Mail;
use App\Models\User;
use App\Models\Location;
use Redirect;
use URL;
use Sentinel;
use Lang;

class UserController extends Controller
{
    

     public function register($history)

    {
       
        return view('pages.register', compact('history'));
    }


    public function registeras()
    {
        return view('pages.register-as');
    }

    public function registerasnot()
    {
        return view('pages.register-asnot');
    }



 /***************************** User profile show data **********************************/

    public function myprofile()
    {         
      if(Sentinel::check()){
           $user = Sentinel::getUser();
           return view('user.myprofile.my-profile', compact('user'));
         } else {
             return redirect('/');
         }
    }
    
 /***************************** User profile show data **********************************/


 /***************************** User profile Update **********************************/

 public function myprofile_update(Request $request){
         
         $data = $request->input();
        if(isset($request->pen_name) && !empty($request->pen_name)){
                    $checkpen_name = User::where('pen_name', $request->pen_name)->where('id', '!=', $request->id)->first();
                      if ($checkpen_name) {
                          return 1;
                  
                      } 
                   $mypen_name = $request->pen_name;
                  }else{
                     $lname=$data['last_name'];
                     $mypen_name = $data['first_name'].''.$lname[0];
                  }
    if(isset($request->pen_name) && !empty($request->pen_name)){
      $checkUsers = User::where('pen_name', $request->pen_name)->where('id', '!=', $request->id)->withTrashed()->first();
        if (!empty($checkUsers->deleted_at)) {
         //return redirect()->back()->with('warning',  'This pen name is already registered with us.');
          return 1;
        }
        $mypen_name = $request->pen_name;
      }else{
         $lname=$data['last_name'];
         $mypen_name = $data['first_name'].''.$lname[0];
      }
     

        
        $mydata = User::where('id',$request->id)->update(['first_name'=>$data['first_name'], 'last_name'=>$data['last_name'],'city'=>$request->city,'pen_name' => $mypen_name]);
        if(isset($mydata) && !empty($mydata)){
           return 0;
      }
       
            
    }

/***************************** User profile Update **********************************/


     public function myprofile_edit()
    {
      if(Sentinel::check()){
           $user = Sentinel::getUser();
         }
        return view('user.myprofile.my-profile_edit', compact('user'));
    }

   
     

     
/***************************** Register **********************************/

      public function createuser(Request $request) {

                   $pen_name = $request->pen_name; 
                   $email = $request->email;
                   if(isset($pen_name) && !empty($pen_name)){
                    $checkpen_name = User::where('pen_name', $pen_name)->first();
                      if ($checkpen_name) {
                         return response()->json(['message'=>'warning']);
                          
                      } 

                  }
                   if(isset($pen_name) && !empty($pen_name)){
                       $checkpen_name = User::where('pen_name', $pen_name)->withTrashed()->first();                  
                         if (!empty($checkpen_name->deleted_at)) {
                         return response()->json(['message'=>'warning']);
                          
                      } 

                  }
               
                 $checkUser = User::where('email', $email)->first();
                    if ($checkUser) {
                         return response()->json(['message'=>'emailwarning']);
                        
                    } 

                    $checkUsers = User::where('email', $request->email)->withTrashed()->first();
                    if (!empty($checkUsers->deleted_at)) {
                      return response()->json(['message'=>'emailwarning']);
                    }

                    $usercredentials = ['email' => $email, 'password' => $request->password];
                    $user = Sentinel::register($usercredentials);
                     $activation = Activation::create($user);
                    if(isset($user) && !empty($user)){
                        //$user->pen_name = $pen_name;
                        $lastid = $user->id;
                        if(isset($request->history) && !empty($request->history) && $request->history =="incarceration"){ 
                              $history ="Yes";
                        }elseif(isset($request->history) && !empty($request->history) && $request->history =="incarcerated"){
                             $history ="No";
                        } else{
                           $history = "";
                        }

                        User::where('id',$lastid)->update(['pen_name' => $pen_name, 'incarcerated_history'=>$history]);
  
                          $userdata = Sentinel::findById($lastid);
                          $role = Sentinel::findRoleByName('User');
                          $role->users()->attach($userdata);

                         $activecode = $activation->code;
                         $userid=base64_encode($lastid);
                         $activationUrl = url('/').'/activation/'.$userid.'/'.$activecode;
                         $replace_with = array($activationUrl);
                         $template_name="sign_up_activation";
                         $email = $this->email($replace_with, $email, $template_name);
                    }

                    //return Redirect::route("prompt-list")->with('success', 'User data has been successfully saved.');

                    return response()->json(['message'=>'success']);

      }

/***************************** Register **********************************/

/****************** Register Activated ****************************/
public function activation($id, $code){
      
      $user = Sentinel::findById(base64_decode($id));

    if (!Activation::complete($user, $code))
    {
        if (Activation::completed($user))
        {
            return redirect('login')->with('warning',  'User is already activated. Try to log in.');
        } 
        else {  
          return redirect('login')->with('warning',  'Your account has not been activated.');
           }
      
    }else {
       return redirect('login')->with('success',  'Your account has been activated successfully. You can now login.');
    }
    
}

/****************** Register Activated ****************************/

public function myprofile_change_password()
      {     if(Sentinel::check()){
              return view('user.myprofile.my-profile_change_password');
            } else{
              return redirect('/');
            }
    }

public function change_password_setting(Request $request){
    
    if(Sentinel::check()){
           $user = Sentinel::getUser(); 
            $userid = Sentinel::getUser()->id;
            $old_password  =  $request->oldpassword;
            $new_password  = Hash::make($request->newpassword);
              if($old_password != $request->newpassword) {
                  if (password_verify($old_password, $user->password)){
                    User::where('id',$userid)->update(['password'=>$new_password]);
                    return 1;
                }
             
            }else{
               return 2;
            } 


            

      }

}


 public function changeemail()
    {
       if(Sentinel::check()){
        return view('user.myprofile.my-profile-change_email');
         } else{
              return redirect('/');
            }
    }

 
 public function change_email(Request $request)
    {
       if(Sentinel::check()){
          $checkUser = User::where('email', $request->email_id)->first();
          if ($checkUser) {
              return 1;
          } else{  
            $checkUsers = User::where('email', $request->email_id)->withTrashed()->first();
            if (isset($checkUsers)) {
               return 1;
            }else{
              $user = Sentinel::getUser(); 
              $userid = base64_encode($user->id);
              $link = url('/').'/email-verified/'.$request->email_id.'/'.$userid;
              $template_name="email_verified";
              $replace_with = array($link);
              $email = $this->email($replace_with, $request->email_id,  $template_name);
             return 2;
          }

        }

         } 
    }

public function emailupdate($email, $id)
    {
       
              $user = Sentinel::findById(base64_decode($id));
              Sentinel::update($user, ['email' => $email]);
              if(Sentinel::check()){
                 return redirect("my-profile-change-email")->with('success', 'Your email has been successfully update.');
              } else{
                
                return redirect('/')->with('success', 'Your email has been successfully update.');
              }
             
    }


/************* Forgot Send Email And Update Password ******************/

     public function resetpassword()
    {
       return view('pages.email-password');
    }
   

   public function forgotpassword(Request $request)
    {

             $this->validate($request, ['email'  => 'required']);
              $Checkuser = User::where('email', request('email'))->first();
     if(!empty($Checkuser)){
           $sentinelUser = Sentinel::findById($Checkuser->id);
            $reminder = Reminder::exists($sentinelUser) ? : Reminder::create($sentinelUser);
            if($reminder == true){
                $oldreminder = Reminder::where('user_id', $sentinelUser->id)->where('completed', 0)->first();
                 $token = $oldreminder->code;
                 $userid=base64_encode($sentinelUser->id);
                 $link = url('/').'/password/'.$userid.'/'.$token;
                 $replace_with = array($sentinelUser->first_name, $link);
                 $template_name="forgot_password";
                 $email = $this->email($replace_with,$sentinelUser->email, $template_name);
                 return redirect('/')->with('success',  'Password reset link has been sent to your email. Please check your inbox.');
            } else {
                $token = $reminder->code;
                $userid=base64_encode($sentinelUser->id);
                $link = url('/').'/password/'.$userid.'/'.$token;
                $replace_with = array($sentinelUser->first_name, $link);
                $template_name="forgot_password";
                $email = $this->email($replace_with,$sentinelUser->email, $template_name);
               return redirect('/')->with('success',  'Password reset link has been sent to your email. Please check your inbox.');
          }

       }else{
           return redirect()->back()->with('warning',  'The email you have entered does not exist.');
       }
    }
    

    public function getpassword($id, $token)
    {
      
       return view('pages.reset-password', compact('id', 'token'));
    }


    public function updatepassword(Request $request)
    {   
         $this->validate($request, ['password'  => 'required']);
         
        $user_id = base64_decode($request->userid);
        $user = Sentinel::findById($user_id);
     if(!empty($user)){
           if ($reminder = Reminder::complete($user, $request->token, $request->password))
         {
            
            return redirect('login')->with('success',  'Your password has been changed successfully.');

         } else{
           return redirect()->back()->with('warning',  'Your link session has been expired. Please try again.');
         }
        
       }else{
         return redirect()->back()->with('warning',  'Your password has been not changed successfully.');
       }
    }

/************* Forgot Send Email And Update Password ******************/


public function proxypassword($id, $token)
    {
      
       return view('pages.proxy-password', compact('id', 'token'));
    }

 public function proxyupdatepassword(Request $request)
    {   
         $this->validate($request, ['password'  => 'required']);
         
        $user_id = base64_decode($request->userid);
        $user = Sentinel::findById($user_id);
     if(!empty($user)){
           if ($reminder = Reminder::complete($user, $request->token, $request->password))
         {
            
            return redirect('login')->with('success',  'Your password has been saved.');

         } else{
           return redirect()->back()->with('warning',  'Your link session has been expired. Please try again.');
         }
        
       }else{
         return redirect()->back()->with('warning',  'Your password has been not saved.');
       }
    }


}

