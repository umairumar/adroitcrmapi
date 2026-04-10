<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\V1\CrmCompanyController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\CrmLeadController;
use App\Http\Controllers\Api\V1\LeadRemarkController;
use App\Http\Controllers\Api\V1\FoldersController;
use App\Http\Controllers\Api\V1\CrmPaymentController;

Route::prefix('v1')->group(function () {

    Route::post('/leadsdirectstore', [CrmLeadController::class, 'directstore']);

    //Route::get('/updatedata', [UserController::class, 'updatedata']);
    //Route::get('/login-test', fn () => 'LOGIN ROUTE OK');
    Route::post('/login', [AuthController::class, 'login']);

    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::put('/userpasswupdate', [UserController::class, 'userpasswupdate']);

    Route::middleware('auth:sanctum')->group(function () {
        
        Route::get('/me', function (Request $request) {
            return $request->user();
        });

        Route::post('/logout', [AuthController::class, 'logout']);

        // Companies APIs
            Route::get('/companies', [CrmCompanyController::class, 'index']); 
            Route::get('/companies/{id}', [CrmCompanyController::class, 'show']);  
            Route::post('/companies', [CrmCompanyController::class, 'store']);     
            Route::put('/companies/{id}', [CrmCompanyController::class, 'update']);
            Route::delete('/companies/{id}', [CrmCompanyController::class, 'destroy']);
            Route::get('/usersbycompany/{company}', [UserController::class, 'usersbycompany']);


        // Users APIs
            Route::get('/users', [UserController::class, 'index']);
            Route::get('/users/{id}', [UserController::class, 'show']);
            Route::post('/users', [UserController::class, 'store']);
            Route::put('/users/{id}', [UserController::class, 'update']);
            Route::delete('/users/{id}', [UserController::class, 'destroy']);
            
        // CRM Leeds
            Route::get('/leads', [CrmLeadController::class, 'index']);
            Route::get('/leads/{id}', [CrmLeadController::class, 'show']);
            Route::post('/leads', [CrmLeadController::class, 'store']);
            Route::put('/leads/{id}', [CrmLeadController::class, 'update']);
            Route::delete('/leads/{id}', [CrmLeadController::class, 'destroy']);

            Route::get('/openleads', [CrmLeadController::class, 'openleads']);
            Route::put('/leadsassign/{id}', [CrmLeadController::class, 'leadsassign']);

            Route::put('/movetobookedleads/{id}', [CrmLeadController::class, 'movetobookedleads']);
            Route::get('/bookedleads', [CrmLeadController::class, 'bookedleads']);

            Route::put('/movetonotbookedleads/{id}', [CrmLeadController::class, 'movetonotbookedleads']);
            Route::get('/notbookedleads', [CrmLeadController::class, 'notbookedleads']);            

            Route::put('/movetoarchiveleads/{id}', [CrmLeadController::class, 'movetoarchiveleads']);
            Route::get('/archiveleads', [CrmLeadController::class, 'archiveleads']);

            Route::get('/allleads', [CrmLeadController::class, 'allleads']);

            Route::get('/leads/company/{companyId}', [CrmLeadController::class, 'leadsByCompany']);
            Route::get('/leads/agent/{agentId}', [CrmLeadController::class, 'leadsByAgent']);

        // Leads Remarks
            Route::get('/leads/{leadId}/remarks', [LeadRemarkController::class, 'index']);
            Route::get('/leads/remarks/{id}', [LeadRemarkController::class, 'show']);  
            Route::post('/leads/remarks', [LeadRemarkController::class, 'store']);
            Route::put('/leads/remarks/{id}', [LeadRemarkController::class, 'update']);
            Route::delete('/leads/remarks/{id}', [LeadRemarkController::class, 'destroy']);
            
        // CRM Folders
            Route::get('/folders', [FoldersController::class, 'index']);        
            Route::get('/folders/{id}', [FoldersController::class, 'show']);       
            Route::post('/folders', [FoldersController::class, 'store']);         
            Route::put('/folders/{id}', [FoldersController::class, 'update']);     
            Route::delete('/folders/{id}', [FoldersController::class, 'destroy']); 

        // Folder Payments
            Route::get('/folders/{folderId}/payments', [CrmPaymentController::class, 'index']);
            Route::post('/folders/{folderId}/payments', [CrmPaymentController::class, 'store']);
            Route::get('/payments/{paymentId}', [CrmPaymentController::class, 'show']);
            Route::put('/payments/{paymentId}', [CrmPaymentController::class, 'update']);
            Route::put('/payments/{paymentId}/process', [CrmPaymentController::class, 'process']);
            Route::delete('/payments/{paymentId}', [CrmPaymentController::class, 'destroy']);
            

    });
});