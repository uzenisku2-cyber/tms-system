<?php

namespace Tests\Unit;

use Tests\TestCase;

use App\Modules\Trips\Models\Trip;

use Illuminate\Foundation\Testing\RefreshDatabase;


class TripWorkflowTest extends TestCase
{

    use RefreshDatabase;



    public function test_planned_trip_can_be_assigned(): void
    {

        $trip = new Trip();

        $trip->status = Trip::STATUS_PLANNED;


        $this->assertTrue(
            $trip->canChangeStatus(
                Trip::STATUS_ASSIGNED
            )
        );

    }





    public function test_assigned_trip_can_be_started(): void
    {

        $trip = new Trip();

        $trip->status = Trip::STATUS_ASSIGNED;


        $this->assertTrue(
            $trip->canChangeStatus(
                Trip::STATUS_STARTED
            )
        );

    }





    public function test_started_trip_can_be_finished(): void
    {

        $trip = new Trip();

        $trip->status = Trip::STATUS_STARTED;


        $this->assertTrue(
            $trip->canChangeStatus(
                Trip::STATUS_FINISHED
            )
        );

    }





    public function test_finished_trip_cannot_change_status(): void
    {

        $trip = new Trip();

        $trip->status = Trip::STATUS_FINISHED;


        $this->assertFalse(
            $trip->canChangeStatus(
                Trip::STATUS_STARTED
            )
        );


        $this->assertFalse(
            $trip->canChangeStatus(
                Trip::STATUS_CANCELLED
            )
        );

    }





    public function test_cancelled_trip_cannot_change_status(): void
    {

        $trip = new Trip();

        $trip->status = Trip::STATUS_CANCELLED;


        $this->assertFalse(
            $trip->canChangeStatus(
                Trip::STATUS_STARTED
            )
        );


        $this->assertFalse(
            $trip->canChangeStatus(
                Trip::STATUS_FINISHED
            )
        );

    }


}