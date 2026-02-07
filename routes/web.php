<?php

use App\Http\Controllers\CoreAPIController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\HrAdminController;
use App\Http\Controllers\ApproverController;
use App\Http\Controllers\SubmitterController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\MyTeamController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\CommunicationControlController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DashboardController;

use Symfony\Component\HttpFoundation\Request;

Route::get('/', function () {
    return view('auth.login');
});

Auth::routes();
Route::get('/consultancy_login', [\App\Http\Controllers\ExtLoginController::class, 'login']);

Route::middleware('auth')->group(function () {

    Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('dashboard');
    //Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/management', [DashboardController::class, 'managementDashboard'])->name('management');
        Route::post('/management/data', [DashboardController::class, 'getDashboardData'])->name('management.data');
    });

    //========================Core API=======================
    Route::resource('core_api', CoreAPIController::class);
    Route::get('core_api_sync', [CoreAPIController::class, 'sync'])->name('core_api_sync');
    Route::post('importAPISData', [CoreAPIController::class, 'importAPISData'])->name('importAPISData');

    Route::resource('users', UserController::class);
    Route::post('getUserList', [UserController::class, 'getUserList'])->name('getUserList');
    Route::post('/users/export', [UserController::class, 'export'])->name('users.export');
    Route::put('/users/{user}/password', [UserController::class, 'changePassword'])->name('users.password');
    Route::get('user/{user_id}/permission', [UserController::class, 'give_permission'])->name('give_permission');
    Route::post('user/{user_id}/permission', [UserController::class, 'set_user_permission'])->name('set_user_permission');

    // Role Management Routes
    Route::resource('roles', RoleController::class);
    Route::get('roles/{role}/permissions', [RoleController::class, 'permissions'])
        ->name('roles.permissions');
    Route::put('roles/{role}/permissions', [RoleController::class, 'updatePermissions'])
        ->name('roles.permissions.update');



    // Manpower Requisition Routes
    Route::prefix('requisitions')->group(function () {
        Route::get('/', [\App\Http\Controllers\ManpowerRequisitionController::class, 'index'])->name('requisitions.index');
        Route::get('/create/{type}', [\App\Http\Controllers\ManpowerRequisitionController::class, 'create'])->name('requisitions.create');
        Route::post('/', [\App\Http\Controllers\ManpowerRequisitionController::class, 'store'])->name('requisitions.store');
        Route::get('/{requisition}', [\App\Http\Controllers\ManpowerRequisitionController::class, 'show'])->name('requisitions.show');
        Route::get('/{requisition}/edit', [\App\Http\Controllers\ManpowerRequisitionController::class, 'edit'])->name('requisitions.edit');
        Route::put('/{requisition}', [\App\Http\Controllers\ManpowerRequisitionController::class, 'update'])->name('requisitions.update');
        Route::get('/document/{document}/download', [\App\Http\Controllers\ManpowerRequisitionController::class, 'downloadDocument'])->name('document.download');
    });

    Route::get('/get-cities-by-state', [\App\Http\Controllers\ManpowerRequisitionController::class, 'getCitiesByState'])->name('get.cities.by.state');
    Route::post('/process-pan-card', [DocumentController::class, 'processPANCard'])->name('process.pan.card');
    Route::post('/process-bank-document', [DocumentController::class, 'processBankDocument'])->name('process.bank.document');
    Route::post('/process-aadhaar-card', [DocumentController::class, 'processAadhaarCard'])->name('process.aadhaar.card');

    Route::delete('/delete-document', [DocumentController::class, 'deleteDocument'])->name('delete.document');



    Route::prefix('hr-admin')->name('hr-admin.')->group(function () {

        // Dashboard
        Route::get('/dashboard', [HrAdminController::class, 'dashboard'])->name('dashboard');
        // New Applications
        Route::prefix('applications')->name('applications.')->group(function () {
            Route::get('/new', [HrAdminController::class, 'newApplications'])->name('new');
            Route::get('/new/{requisition}', [HrAdminController::class, 'viewRequisition'])->name('view');
            Route::get('/new/{requisition}/verify', [HrAdminController::class, 'verifyRequisition'])->name('verify');
            Route::post('/new/{requisition}/send-approval', [HrAdminController::class, 'sendForApproval'])->name('send-approval');
            Route::post('/new/{requisition}/request-correction', [HrAdminController::class, 'requestCorrection'])->name('request-correction');
            Route::get('/new/{requisition}/get-edit-form', [HrAdminController::class, 'getEditForm'])->name('get-edit-form');
            Route::put('/new/{requisition}/update', [HrAdminController::class, 'updateSection'])->name('update');

            // Approved Applications
            Route::post('/new/{requisition}/verify-application', [HrAdminController::class, 'verifyApplication'])->name('verify-application');
            Route::get('/approved', [HrAdminController::class, 'approvedApplications'])->name('approved');
            Route::get('get-reporting-managers/{requisition}', [HrAdminController::class, 'getReportingManagers'])->name('get-reporting-managers');
            Route::post('process-modal', [HrAdminController::class, 'processApplicationModal'])
                ->name('process-modal');
            Route::get('upload-agreement/{candidate}', [HrAdminController::class, 'showUploadAgreementByEmployee'])->name('upload-agreement');
            Route::post('upload-agreement/{candidate}', [HrAdminController::class, 'uploadAgreementStoreByEmployee'])->name('upload-agreement-store');
            Route::get('verify-signed/{candidate}', [HrAdminController::class, 'showVerifySignedByEmployee'])->name('verify-signed');
        });

        // Master Tab
        Route::prefix('master')->name('master.')->group(function () {
            Route::get('/{type?}', [HrAdminController::class, 'masterTab'])->name('index');
            Route::get('/employee/{candidate}', [HrAdminController::class, 'viewEmployee'])->name('view-employee');

            // Fix parameter name from employee to candidate
            Route::post('/employee/{candidate}/upload-unsigned', [HrAdminController::class, 'uploadUnsignedAgreement'])->name('upload-unsigned');
            Route::post('/employee/{candidate}/upload-signed', [HrAdminController::class, 'uploadSignedAgreement'])->name('upload-signed');
            Route::post('/employee/{candidate}/verify-signed', [HrAdminController::class, 'verifySignedAgreement'])->name('verify-signed');
            Route::get('/agreement/{agreement}/download', [HrAdminController::class, 'downloadAgreement'])->name('download-agreement');
        });

        Route::get('/document/{document}/download', [HrAdminController::class, 'downloadDocument'])
            ->name('download.document');
        Route::get('candidate/{candidate}/signed-documents', [HrAdminController::class, 'getSignedDocuments'])->name('candidate.signed-documents');
        Route::post('candidate/{candidate}/verify-signed-agreement', [HrAdminController::class, 'verifySignedAgreement'])->name('candidate.verify-signed-agreement');
    });

    Route::prefix('approver')->name('approver.')->group(function () {
        Route::get('/requisition/{requisition}', [ApproverController::class, 'viewRequisition'])
            ->name('requisition.view');
        Route::post('/requisition/{requisition}/approve', [ApproverController::class, 'approveRequisition'])
            ->name('requisition.approve');
        Route::post('/requisition/{requisition}/reject', [ApproverController::class, 'rejectRequisition'])
            ->name('requisition.reject');
    });

    Route::get('/communication-controls', [CommunicationControlController::class, 'index'])->name('communication.index');
    Route::post('/communication-controls', [CommunicationControlController::class, 'store'])->name('communication.store');
    Route::post('/communication-controls/{communicationControl}/toggle', [CommunicationControlController::class, 'toggle'])->name('communication.toggle');
});

// Agreement Management Routes for HR Admin
Route::prefix('hr-admin/agreement')->name('hr-admin.agreement.')->middleware(['auth'])->group(function () {
    Route::get('/list', [HrAdminController::class, 'agreementPendingList'])->name('list');
    Route::get('/{candidate}/management', [HrAdminController::class, 'agreementManagement'])->name('management');
    Route::post('/{candidate}/upload-unsigned', [HrAdminController::class, 'uploadUnsignedAgreement'])->name('upload-unsigned.store');
    Route::post('/{candidate}/upload-signed', [HrAdminController::class, 'uploadSignedAgreement'])->name('upload-signed.store');
    Route::post('/{candidate}/update/{type}', [HrAdminController::class, 'updateAgreement'])->name('update');
    Route::get('/document/{agreement}/download', [HrAdminController::class, 'downloadAgreementDocument'])->name('download');
    Route::get('/document/{agreement}/view', [HrAdminController::class, 'viewAgreementDocument'])->name('view');

    // API endpoint for getting unsigned agreement
    Route::get('/{candidate}/get-unsigned', [HrAdminController::class, 'getUnsignedAgreement'])->name('get-unsigned');
});

// Submitter Agreement Routes (Simplified)
Route::middleware(['auth'])->group(function () {
    Route::prefix('submitter')->name('submitter.')->group(function () {
        // View and download unsigned agreement
        Route::get('/agreement/{requisition}', [SubmitterController::class, 'viewAgreement'])->name('agreement.view');
        Route::get('/agreement/{requisition}/download', [SubmitterController::class, 'downloadAgreement'])->name('agreement.download');

        // Upload signed agreement
        Route::post('/agreement/{requisition}/upload-signed', [SubmitterController::class, 'uploadSignedAgreement'])->name('agreement.upload-signed');
    });
});

// HR Admin Agreement Routes
Route::prefix('hr-admin/agreement')->name('hr-admin.agreement.')->middleware(['auth'])->group(function () {
    Route::get('/list', [HrAdminController::class, 'agreementPendingList'])->name('list');

    // Upload unsigned agreement (HR action)
    Route::post('/{candidate}/upload-unsigned', [HrAdminController::class, 'uploadUnsignedAgreement'])->name('upload-unsigned.store');

    // Upload signed agreement (HR action when received via email)
    Route::post('/{candidate}/upload-signed', [HrAdminController::class, 'uploadSignedAgreement'])->name('upload-signed.store');

    // Update agreement if wrong
    Route::post('/{candidate}/update/{type}', [HrAdminController::class, 'updateAgreement'])->name('update');

    // Download/view
    Route::get('/document/{agreement}/download', [HrAdminController::class, 'downloadAgreementDocument'])->name('download');
    Route::get('/document/{agreement}/view', [HrAdminController::class, 'viewAgreementDocument'])->name('view');


    Route::get('/{agreement}/details', [HrAdminController::class, 'getAgreementDetails'])
        ->name('hr-admin.agreement.details');
    Route::get('/{agreement}/view', [HrAdminController::class, 'viewAgreementDocument'])
        ->name('hr-admin.agreement.view');
    Route::put('/{agreement}/update', [HrAdminController::class, 'updateAgreement'])
        ->name('hr-admin.agreement.update');
});

// Attendance Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');

    // AJAX routes
    Route::prefix('attendance')->group(function () {
        Route::post('/get', [AttendanceController::class, 'getAttendance'])->name('attendance.get');
        Route::post('/get-candidate', [AttendanceController::class, 'getCandidateAttendance'])->name('attendance.get-candidate');
        Route::post('/update', [AttendanceController::class, 'updateAttendance'])->name('attendance.update');
        Route::post('/get-sundays', [AttendanceController::class, 'getSundays'])->name('attendance.get-sundays');
        Route::post('/get-active-candidates', [AttendanceController::class, 'getActiveCandidates'])->name('attendance.get-active-candidates');
        Route::post('/submit-sunday-work', [AttendanceController::class, 'submitSundayWork'])->name('attendance.submit-sunday-work');
        Route::get('/export', [AttendanceController::class, 'export'])->name('attendance.export');
    });

    Route::prefix('my-team')->middleware(['auth'])->group(function () {
        Route::get('/', [MyTeamController::class, 'index'])->name('my-team.index');
        Route::get('/get-candidates', [MyTeamController::class, 'getCandidates'])->name('my-team.get-candidates');
        Route::get('/candidate/{id}', [MyTeamController::class, 'showCandidatePage'])->name('my-team.candidate.show');
        Route::get('/candidate/{id}/documents', [MyTeamController::class, 'getCandidateDocuments'])->name('my-team.candidate.documents');
        Route::get('/export', [MyTeamController::class, 'export'])->name('my-team.export');
    });
});

Route::prefix('hr/salary')->group(function () {
    Route::get('/', [SalaryController::class, 'index'])->name('salary.index');
    Route::post('/process', [SalaryController::class, 'process'])->name('salary.process');
    Route::post('/list', [SalaryController::class, 'list'])->name('salary.list');
    Route::post('/check-exists', [SalaryController::class, 'checkExists'])->name('salary.checkExists');
    Route::get('/payslip/{id}', [SalaryController::class, 'downloadPayslip'])->name('salary.payslip');
    // Detailed Report Routes
    Route::get('/detailed-report', [SalaryController::class, 'detailedReportView'])->name('salary.detailed.report.view');
    Route::post('/detailed-report-data', [SalaryController::class, 'getDetailedReportData'])->name('salary.detailed.report.data');
    Route::get('/export-detailed-report', [SalaryController::class, 'exportDetailedReport'])->name('salary.detailed.report.export');

    // Management Report Routes
    Route::get('/management-report', [SalaryController::class, 'managementReportView'])->name('salary.management.report');
    Route::post('/management-report/data', [SalaryController::class, 'getManagementReportData'])->name('salary.management.report.data');
    Route::get('/export-management-report', [SalaryController::class, 'exportManagementReport'])->name('salary.export.management.report');

    Route::get('/export', [SalaryController::class, 'exportExcel'])->name('salary.export');
    Route::post('/update-arrear', [SalaryController::class, 'updateArrear'])->name('salary.update.arrear');
});


Route::middleware(['auth'])->group(function () {
    Route::post(
        '/candidate/{candidate}/deactivate',
        [CandidateController::class, 'deactivate']
    )->name('candidate.deactivate');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/master', [ReportController::class, 'master'])->name('master');
    Route::get('/master/export', [ReportController::class, 'masterExport'])->name('master.export');
});

// Route::post('/test/agreement-api', function(Request $request) {
//     \Log::info('Agreement API Test Payload Received:', $request->all());
    
//     // Simulate API response
//     return response()->json([
//         'success' => true,
//         'agreement_number' => 'TEST-' . date('Ymd-His'),
//         'file_path' => 'test_agreements/TEST-' . date('Ymd-His') . '.pdf',
//         'message' => 'Test agreement generated successfully',
//         'received_data' => $request->all() // Return back for verification
//     ]);
// });
