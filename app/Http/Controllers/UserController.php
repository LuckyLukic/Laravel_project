<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller {

    public function logout() {

        auth()->logout();
        return redirect('/')->with('success', 'You are logged out');
    }
    public function login(Request $request) {
        $incomingFields = $request->validate([
            'loginusername' => 'required',
            'loginpassword' => 'required'
        ]);

        if(auth()->attempt(['username' => $incomingFields['loginusername'], 'password' => $incomingFields['loginpassword']])) {
            $request->session()->regenerate();
            return redirect('/')->with('success', 'You have successfully logged in');
        } else {

            return redirect('/')->with('failure', 'Invalid login.');
        }
    }

    public function register(Request $request) {
        $incomingFields = $request->validate([
            'username' => ['required', 'min:3', 'max:20', Rule::unique('users', 'username')],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'password' => ['required', 'min:8', 'confirmed',]
        ]);

        $incomingFields['password'] = bcrypt($incomingFields['password']);

        $user = User::create($incomingFields);
        auth()->login($user);

        return redirect('/')->with('success', 'Account Created');
    }

    public function profile(User $user) {
        return view('profile-posts', [
            'username' => $user->username,
            'posts' => $user->posts()->latest()->get(),
            'avatar' => $user->avatar,
        ]);
    }

    public function showCorrectHomepage() {
        if(auth()->check()) {
            return view('homepage-feed');
        } else {
            return view('homepage');
        }

    }

    public function showAvatarForm() {

        return view('avatar-form');
    }

    public function storeAvatar(Request $request) {

        $request->validate([
            'avatar' => 'required|image|max:2000',
        ]);

        $user = auth()->user();
        $filename = $user->id.'-'.uniqid().'.jpg';

        // $request->file('avatar')->store('public/avatars'); storing the real upload img size
        $imgData = Image::make($request->file('avatar'))->fit(120)->encode('jpg'); //120 = tot pix per lato
        Storage::put('public/avatars/'.$filename, $imgData);

        $oldAvatar = $user->avatar;

        $user->avatar = $filename;
        $user->save();

        if($oldAvatar != "/fallback-avatar.jpg") {
            Storage::delete(str_replace("/storage/", "/public/", $oldAvatar));
        }

        return back()->with('success', 'COngrats onthe new Avatar!!');

    }
}
