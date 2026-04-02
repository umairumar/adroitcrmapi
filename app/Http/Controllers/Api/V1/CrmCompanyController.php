<?php
namespace App\Http\Controllers\Api\V1; 

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; 
use App\Models\CrmCompany; 


class CrmCompanyController extends Controller
{
    // LIST ALL
    public function index(Request $request)
    {
            $authUser = $request->user();

            $crmCompany = CrmCompany::query();

            if ($authUser->utype === 'sadmin') {
                // Director → sees all companies

            } elseif ($authUser->utype === 'cadmin') {
                // Manager → sees ONLY assigned companies

                // Example: "-2-4-" → [2,4]
                $companyIds = explode('-', trim($authUser->company, '-'));

                $crmCompany->whereIn('id', $companyIds);

            } else {
                // Others → no access
                $crmCompany->whereRaw('1 = 0');
            }

            return response()->json([
                'success' => true,
                'data'    => $crmCompany->get()
            ]);
    }

    // INSERT
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'title'  => 'required|string|max:500',
                'email'  => 'required|email|max:250',
                'phone'  => 'required|max:100',
                'status' => 'required|in:0,1',
            ],
            [
                'title.required'  => 'Company title is required',
                'email.required'  => 'Email address is required',
                'email.email'     => 'Email format is invalid',
                'phone.required'  => 'Phone number is required',
                'status.required' => 'Status is required',
                'status.in'       => 'Status must be 0 or 1',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = time().'_'.$file->getClientOriginalName();
                $file->move(public_path('uploads/company'), $filename);
            } else {
                $filename = null;
            }

        $company = CrmCompany::create([
            'title'          => $request->title,
            'address'        => $request->address,
            'email'          => $request->email,
            'phone'          => $request->phone,
            'status'         => $request->status,
            'subscribe_link' => $request->subscribe_link,
            'web_address'    => $request->web_address,
            'image'          => $filename,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Company created successfully',
            'data'    => $company
        ], 201);
    }

    // GET SINGLE INFO.
    public function show($id)
    {
        $company = CrmCompany::find($id);

        if (!$company) {
            return response()->json([
                'status'  => false,
                'message' => 'Company not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $company
        ]);
    }


    // UPDATE
    public function update(Request $request, $id)
    {
        $company = CrmCompany::find($id);
        
        if (!$company) {
            return response()->json([
                'status'  => false,
                'message' => 'Company not found'
            ], 404);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'title'  => 'required|string|max:500',
                'email'  => 'required|email|max:250',
                'phone'  => 'required|max:100',
                'status' => 'required|in:0,1',
            ],
            [
                'title.required'  => 'Company title is required',
                'email.email'     => 'Invalid email format',
                'status.in'       => 'Status must be 0 or 1',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        
        $data = $request->all();

        if ($request->hasFile('image')) {
             if ($company->image && file_exists(public_path('uploads/company/'.$company->image))) {
                 unlink(public_path('uploads/company/'.$company->image));
             }

             $file = $request->file('image');
             $filename = time().'_'.$file->getClientOriginalName();
             $file->move(public_path('uploads/company'), $filename);
             $data["image"] = $filename;
        }

        $company->update($data);
    
        return response()->json([
            'status'  => true,
            'message' => 'Company updated successfully',
            'data'    => $company
        ]);
    }

    // DELETE
    public function destroy($id)
    {
        $company = CrmCompany::find($id);

        if (!$company) {
            return response()->json([
                'status'  => false,
                'message' => 'Company not found'
            ], 404);
        }

        if ($company->image && file_exists(public_path('uploads/company/'.$company->image))) {
            unlink(public_path('uploads/company/'.$company->image));
        }

        $company->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Company deleted successfully'
        ]);
    }
}