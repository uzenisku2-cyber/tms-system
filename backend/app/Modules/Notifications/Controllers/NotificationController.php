<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Controllers;


use App\Core\Http\BaseController;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;



class NotificationController extends BaseController
{


    /**
     * List user notifications
     */
    public function index(
        Request $request
    ): JsonResponse {


        $notifications = $request
            ->user()
            ->notifications()
            ->latest()
            ->paginate(20);



        return $this->paginated(
            $notifications,
            'Notifications loaded.'
        );

    }





    /**
     * Unread notifications only
     */
    public function unread(
        Request $request
    ): JsonResponse {


        $notifications = $request
            ->user()
            ->unreadNotifications()
            ->latest()
            ->get();



        return $this->success(

            [

                'count' => $notifications->count(),

                'notifications' => $notifications,

            ],

            'Unread notifications loaded.'

        );

    }





    /**
     * Mark notification as read
     */
    public function read(
        Request $request,
        string $id
    ): JsonResponse {


        if (! Str::isUuid($id)) {

            return $this->error(
                'Invalid notification id.',
                422
            );

        }



        $notification = $request
            ->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();



        $notification->markAsRead();



        return $this->success(
            null,
            'Notification marked as read.'
        );

    }





    /**
     * Mark all notifications as read
     */
    public function readAll(
        Request $request
    ): JsonResponse {


        $request
            ->user()
            ->unreadNotifications()
            ->update([

                'read_at' => now(),

            ]);



        return $this->success(
            null,
            'All notifications marked as read.'
        );

    }


}