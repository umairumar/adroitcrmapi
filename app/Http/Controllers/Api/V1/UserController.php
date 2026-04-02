<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

use App\Models\User;

class UserController extends Controller
{
    // LIST USERS
    public function index(Request $request)
    {
            $authUser = $request->user();

            $perPage = $request->input('per_page', 9);
            
            $users = User::query();

            // --------------------------------------------
            // Role & company-based filtering
            // --------------------------------------------
            if ($authUser->utype === 'sadmin') {
                // Director → sees all users (optional: you can also exclude himself if needed)
            } elseif ($authUser->utype === 'cadmin') {

                // cadmin sees ONLY agents of same company
                $companies = explode('-', trim($authUser->company, '-'));
                $users->where('utype', 'agent')        // ONLY agents
                ->where('id', '<>', $authUser->id)    // exclude self
                ->where(function ($q) use ($companies) {
                    foreach ($companies as $companyId) {
                        $q->orWhere('company', 'like', "%-{$companyId}-%");
                    }
                });

            } else {
                // Other roles → empty
                $users->whereRaw('1 = 0');
            }

            
            if ($request->filled('search')) {
                $users->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('email', 'like', '%' . $request->search . '%');
                });
            }
            
            $users = $users
                ->orderByRaw("
                    CASE 
                        WHEN utype = 'sadmin' THEN 1
                        WHEN utype = 'cadmin' THEN 2
                        ELSE 3
                    END
                ")
                ->orderBy('name', 'asc')
                ->paginate($perPage);
        
            return response()->json([
                'status' => true,
                'data'   => $users->items(),
                'meta'   => [
                    'current_page' => $users->currentPage(),
                    'last_page'    => $users->lastPage(),
                    'per_page'     => $users->perPage(),
                    'total'        => $users->total(),
                ]
            ]);
    }

    // GET SINGLE USER
    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $user
        ]);
    }

    // CREATE USER
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email|unique:user,email',
            'password' => 'required|min:6',
            'utype'    => 'required',
            'name'     => 'required',
            'agent_directline'  => 'required|max:100',
            'company'  => 'required',
            'status'   => 'required|in:0,1'
        ], [
            'email.required' => 'Email is required',
            'email.unique'   => 'Email already exists',
            'password.min'   => 'Password must be at least 6 characters'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time().'_'.$file->getClientOriginalName();
            $file->move(public_path('uploads/user'), $filename);
        } else {
            $filename = null;
        }

        
        $user = User::create([
            'email'                 => $request->email,
            'password'              => Hash::make($request->password), //Hash::make($request->password)
            'utype'                 => $request->utype,
            'name'                  => $request->name,
            'company'               => $request->company,
            'agent_directline'      => $request->agent_directline,
            'mon_fri'               => $request->mon_fri,
            'att_sat'               => $request->att_sat,
            'att_sun'               => $request->att_sun,
            'status'                => $request->status,
            'cby'                   => $request->cby ?? 0,
            'cdate'                 => now(),
            'mby'                   => $request->mby ?? 0,
            'mdate'                 => now(),
            'dateofjoining'         => $request->dateofjoining,
            'phoneno'               => $request->phoneno,
            'reference_name1'       => $request->reference_name1,
            'reference_name2'       => $request->reference_name2,
            'reference_phone1'      => $request->reference_phone1,
            'reference_phone2'      => $request->reference_phone2,
            'user_address'          => $request->user_address,
            'image'                 => $filename
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'User created successfully',
            'data'    => $user
        ], 201);
    }

    // UPDATE USER
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'sometimes|email|unique:user,email,' . $id,
            'status' => 'sometimes|in:0,1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();

        if ($request->password) {
            $data['password'] = Hash::make($request->password); //Hash::make($request->password)
        } else {
            unset($data['password']);
        }

        if ($request->hasFile('image')) {
             if ($user->image && file_exists(public_path('uploads/user/'.$user->image))) {
                 unlink(public_path('uploads/user/'.$user->image));
             }

             $file = $request->file('image');
             $filename = time().'_'.$file->getClientOriginalName();
             $file->move(public_path('uploads/user'), $filename);
             $data["image"] = $filename;
        }

        $data['mdate'] = now();

        $user->update($data);

        return response()->json([
            'status'  => true,
            'message' => 'User updated successfully',
            'data'    => $user
        ]);
    }

    // DELETE USER
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        if ($user->image && file_exists(public_path('uploads/user/'.$user->image))) {
            unlink(public_path('uploads/user/'.$user->image));
        }

        $user->delete();

        return response()->json([
            'status'  => true,
            'message' => 'User deleted successfully'
        ]);
    }

    // UPDATE USER
    public function updatedata()
    {
        User::whereRaw('LENGTH(password) > 0')->chunk(100, function ($users) {
            foreach ($users as $user) {
                $user->password = Hash::make($user->password);
                $user->save();
            }
        });
        dd ("ok");
    }
    
    // UPDATE SINGLE USER PASSWORD UPDATE
    public function userpasswupdate(Request $request)
    {
        
        $user = User::where('email',$request->email)->first();

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'User not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:0,1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->password) {
            $data['password'] = Hash::make($request->password); //Hash::make($request->password)
        } else {
            unset($data['password']);
        }

        $data['mdate'] = now();

        $user->update($data);

        return response()->json([
            'status'  => true,
            'message' => 'User updated successfully',
            'data'    => $user
        ]);
    }

    // GET USERS BY COMPANY
    public function usersbycompany($company)
    {
        $user = User::where('company','LIKE','%-'.$company.'-%')
        ->orderByRaw("
            CASE 
                WHEN utype = 'cadmin' THEN 1
                ELSE 2
            END
        ")
        ->orderby('name','asc')->get();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $user
        ]);
    }
    
}
