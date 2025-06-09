<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\FriendController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Api\FollowController;
use App\Http\Controllers\Api\CitizenController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\ServiceTypeController;
use App\Http\Controllers\Api\CitizenServiceRequestController;
use App\Http\Controllers\Api\EmployeeServiceRequestController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\CitizenComplaintController;
use App\Http\Controllers\Api\InstituteComplaintController;
use App\Http\Controllers\Api\EmployeeComplaintController;
use App\Http\Controllers\Api\SocialLinkController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\BillModule;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify', [AuthController::class, 'verifyEmail']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forget-password', [AuthController::class, 'forgetPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::middleware('auth:sanctum')->post('/change-password', [AuthController::class, 'changePassword']);
Route::middleware('auth:sanctum')->group(function () {

    // 1. Send a friend request
    Route::post('/friend-request/send', [FriendController::class, 'sendFriendRequest']);

    // 2. Respond to a friend request (accept or cancel)
    Route::post('/friend-request/respond/{id}', [FriendController::class, 'respondToFriendRequest']);

    // 4. Unfriend a user
    Route::delete('/friends/unfriend/{friend_id}', [FriendController::class, 'unfriend']);

    // 5. List all friends of authenticated user
    Route::get('/friends', [FriendController::class, 'listFriends']);
    // 6. Search for friends from friend list
    // http://localhost:8000/api/friends/search?search=ahmed
     Route::get('/friends/search', [FriendController::class, 'searchFriends']);

    Route::get('citizen/friends/count', [FriendController::class, 'countFriends']);
    Route::get('/citizen/notifications', [NotificationController::class, 'citizenNotifications']);
    Route::get('/institute/notifications', [NotificationController::class, 'instituteNotifications']);
    Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markAsRead']);
    Route::post('/follow', [FollowController::class, 'follow']);
    Route::post('/unfollow', [FollowController::class, 'unfollow']);
    Route::get('/my-follows', [FollowController::class, 'listFollows']);
        // http://localhost:8000/api/friends/search?q=Minstry

        Route::get('/my-follows', [FollowController::class, 'searchFollowedInstitutes']);

    Route::post('/citizen/profile', [CitizenController::class, 'updateCitizenProfile']);
Route::get('/public-profile/{username}', [CitizenController::class, 'show']);

});
Route::get('/public-profile/{username}', [CitizenController::class, 'show']);


Route::middleware('auth:sanctum')->group(function () {



    // Citizen routes
    Route::get('/citizen/announcements', [AnnouncementController::class, 'citizenFeed']);
    Route::get('/citizen/announcements/{id}', [AnnouncementController::class, 'showAnnouncementForCitizen']);

    Route::post('/citizen/announcements/{id}/like', [AnnouncementController::class, 'like']);
    Route::post('/citizen/announcements/{id}/comment', [AnnouncementController::class, 'comment']);
    Route::post('/citizen/announcements/{id}/seen', [AnnouncementController::class, 'markAsSeen']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadNotificationCount']);
});

Route::middleware(['auth:sanctum', 'type:government-institute'])->group(function () {
    Route::post('institute/announcements/{id}/update', [AnnouncementController::class, 'updateByInstitute']);
    Route::delete('institute/announcements/{id}/delete', [AnnouncementController::class, 'destroyByInstitute']);
    Route::get('/institute/announcements', [AnnouncementController::class, 'instituteAnnouncements']);
    Route::get('/institute/announcements/{id}', [AnnouncementController::class, 'showForInstitute']);

    // Institute routes
    Route::post('/institute/announcements/{id}/approve', [AnnouncementController::class, 'approve']);
    Route::post('/institute/announcements/{id}/reject', [AnnouncementController::class, 'reject']);
});
Route::middleware(['auth:sanctum', 'type:government-employee'])->group(function () {
    Route::post('/employee/newannouncements', [AnnouncementController::class, 'store']);
    Route::post('/employee/announcements/{id}/update', [AnnouncementController::class, 'updateByEmployee']);
    Route::delete('/employee/announcements/{id}/delete', [AnnouncementController::class, 'destroyByEmployee']);
    Route::get('/employee/announcements', [AnnouncementController::class, 'getAllByEmployee']);

    // Notifications
    Route::get('/employee/notifications', [NotificationController::class, 'employeeNotifications']);
});


Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/institute/services', [ServiceTypeController::class, 'index']);
    Route::post('/institute/services/add', [ServiceTypeController::class, 'store']);
    Route::put('/institute/services/{id}', [ServiceTypeController::class, 'update']);
    Route::delete('/institute/services/{id}', [ServiceTypeController::class, 'destroy']);
    //for list for incoming requests

    //GET /api/institute/incoming-requests?status=pending
    // GET /api/institute/incoming-requests

    Route::get('/institute/incoming-requests', [ServiceTypeController::class, 'incomingRequests']);
    // assign employee to request
    Route::post('/institute/incoming-requests/assign-employee', [ServiceTypeController::class, 'assignEmployeeToRequest']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/citizen/service-requests', [CitizenServiceRequestController::class, 'index']);
    Route::post('/citizen/service-requests/add', [CitizenServiceRequestController::class, 'store']);
    //for dropdown government name filter
    Route::get('/citizen/institutes-name', [CitizenServiceRequestController::class, 'getInstitutesByGovernorate']);
    //for dropdown service type filter
    Route::get('/citizen/institutes/{id}/service-types', [CitizenServiceRequestController::class, 'getServiceTypesByInstitute']);
});

Route::middleware('auth:sanctum')->prefix('employee')->group(function () {
    //GET /api/employee/assigned-requests?status=pending
    // GET /api/employee/assigned-requests
    Route::get('/assigned-requests', [EmployeeServiceRequestController::class, 'assignedRequests']);
    Route::get('/assigned-requests/{id}', [EmployeeServiceRequestController::class, 'showAssignedRequest']);
    Route::put('/assigned-requests/{id}/status', [EmployeeServiceRequestController::class, 'updateRequestStatus']);
    Route::post('/profile', [EmployeeController::class, 'updateEmployeeProfile']);
});


// Citizen routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/citizen/complaints', [CitizenComplaintController::class, 'index']);
    Route::post('/citizen/complaints/add', [CitizenComplaintController::class, 'store']);
});

// Institute routes
Route::middleware('auth:sanctum')->group(function () {
    // GET /api/institute/complaints?status=pending
    Route::get('/institute/complaints', [InstituteComplaintController::class, 'getInstituteComplaints']);
    Route::post('/institute/complaints/{id}/assign', [InstituteComplaintController::class, 'assignComplaint']);
});

// Employee routes
Route::middleware('auth:sanctum')->group(function () {
    // GET /api/employee/complaints?status=pending
    Route::get('/employee/complaints', [EmployeeComplaintController::class, 'getAssignedComplaints']);
    Route::get('/employee/complaints/{id}', [EmployeeComplaintController::class, 'showComplaint']);
    Route::put('/employee/complaints/{id}/status', [EmployeeComplaintController::class, 'updateComplaintStatus']);
});

// routes/api.php
Route::middleware('auth:sanctum')->prefix('citizen/social-links')->group(function () {
    Route::get('/', [SocialLinkController::class, 'index']);
    Route::post('/add', [SocialLinkController::class, 'store']);
    Route::put('/{id}', [SocialLinkController::class, 'update']);
    Route::delete('/{id}', [SocialLinkController::class, 'destroy']);
});
Route::middleware('auth:sanctum')->prefix('institute/social-links')->group(function () {
    Route::get('/', [SocialLinkController::class, 'index']);
    Route::post('/add', [SocialLinkController::class, 'store']);
    Route::put('/{id}', [SocialLinkController::class, 'update']);
    Route::delete('/{id}', [SocialLinkController::class, 'destroy']);
});
// routes/api.php
//http://localhost:8000/api/search?query=ahmed
Route::middleware('auth:sanctum')->get('/search', [SearchController::class, 'search']);
Route::middleware('auth:sanctum')->get('/search/{id}', [SearchController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/messages', [MessageController::class, 'store']);
    Route::get('/messages/chat/{userId}', [MessageController::class, 'chatWith']);
    Route::get('/messages/inbox', [MessageController::class, 'inbox']);
    // GET /api/messages/unread-count

    Route::get('/messages/unread-count', [MessageController::class, 'unreadMessageCount']);
    //  PATCH /api/messages/{id}/read
    Route::patch('/messages/{id}/read', [MessageController::class, 'markMessageAsRead']);
});



Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/institute/bills', [BillModule::class, 'index']);
    Route::post('/institute/bills/add', [BillModule::class, 'createBillType']);
    Route::put('/institute/bills/{id}', [BillModule::class, 'update']);
    Route::delete('/institute/bills/{id}', [BillModule::class, 'destroy']);

    //GET /api/institute/assigned-bills?status=paid
    // GET /api/institute/assigned-bills

    Route::get('/institute/assigned-bills', [BillModule::class, 'instituteBills']);
});


Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/employee/assign-bill', [BillModule::class, 'assignBill']);

    //GET /api/employee/assigned-bills?status=paid
    // GET /api/employee/assigned-bills
    Route::get('/employee/bills', [BillModule::class, 'employeeAssignedBills']);

    Route::get('/citizen/assigned-bills', [BillModule::class, 'citizenBills']);
    Route::put('/citizen/assigned-bills/pay/{id}', [BillModule::class, 'payBill']);
});
