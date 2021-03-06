<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use App\User;
class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct(){
        $this->middleware('auth:api');
    }
    public function index()
    {
        // $this->authorize('isAdmin');
        if( \Gate::allows('isAdmin') || \Gate::allows('isAuthor') ){
            return User::latest()->paginate(10);
        }
    }
    public function profile(){
        return auth('api')->user();
    }
    public function updateProfile(Request $request){
        $user = auth('api')->user();
        $this->validate($request,[
            'name'=> 'required|string|max:191',
            'email'=> 'required|string|email|max:191|unique:users,email,'.$user->id,
            'password'=> 'sometimes|required|min:6',
        ]);
        $currentPhoto = $user->photo;
        /*Almacenar foto en la carpeta images/profile */
        if( $request->photo != $currentPhoto){
            $name = time().'.'. explode('/', explode(':',substr($request->photo,0,
                strpos($request->photo,';')))[1])[1];
            \Image::make($request->photo)->save(public_path('images/profile/').$name);
            $request->merge(['photo' => $name]);
            /*Eliminar foto anterior*/
            $oldPhoto = public_path('images/profile/') . $currentPhoto;
            if( file_exists($oldPhoto)){
                @unlink($oldPhoto);
            }
        }
        if(!empty($request->password)){
            $request->merge([
                'password' => Hash::make($request->password)
            ]);
        }
        $user->update($request->all());
        return ['message'=> 'success'];
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request,[
            'name' => 'required|string|max:191',
            'email'=> 'required|string|email|max:191|unique:users',
            'password' => 'required|string|min:6',
        ]);
        return User::create([
            'name'  => $request['name'],
            'email' => $request['email'],
            'type'  => $request['type'],
            'bio'   => $request['bio'],
            'photo' => $request['photo'],
            'password' => Hash::make($request['password']),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        
        $user = User::findOrFail($id);
        $this->validate($request,[
            'name'=> 'required|string|max:191|',
            'email'=>'required|string|email|max:191|unique:users,email,'.$user->id,
            'password'=>'sometimes|min:6',
        ]);

        $user->update($request->all());
        return ['message' => 'Updated the user info'];
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->authorize('isAdmin');
        $user = User::findOrFail($id);
        $user->delete();
        return ['message' => 'User deleted'];
    }
    public function search(){
        $users = User::latest()->paginate(10);
        if( $search = \Request::get('q')){
            $users = User::where( function($query) use ($search){
                $query->where('name','LIKE',"%$search%")
                ->orWhere('email','LIKE',"%$search%")
                ->orWhere('type','LIKE',"%$search%");
            })->latest()->paginate(10);
        }
        return $users;
    }
}
