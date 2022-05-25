<?php

namespace Tests\Feature;

use app\Http\Controllers\v4\Api\Merchant\GetHome;
use Tests\TestCase;
use app\User;
use Carbon;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
// use Illuminate\Foundation\Testing\TestCase;

class GetHomeTest extends TestCase
{
   
    /**
     * A basic feature test example.
     *
     * @return void
     */
    
    /**public function test_get_home_api_request() 
    {
        // API testing flow

        // Send the request with necessary input data
        $GH=new GetHome($_REQUEST);
        
        $pricing=300;
        $ratePerHour=30;
        $balance=100;
        $name='John Doe';
        $version='1.1.1';
        $agent='Agent Test';
        $site='site testing';
        $today=Carbon::createFromFormat('Y-m-d H:i:s', '2019-08-24 16:00:00');

        // Get the response having output data
        $response = $this->getJson('/api/merchant/account/home', [
            $pricing,
            $ratePerHour,
            $balance,
            $name,
            $version,
            $agent,
            $site,
            $today,
        ]);
 
        $response
            ->assertStatus(200)
            ->assertJson([
                'created' => true,
            ]);

        // Verify that the response returned as expected in the requirement
        echo $data;

        // BASIC TEST
        // $response = $this->get('/');
        // // dd($response);

        // // $response->assertStatus(200);
        // $this->assertTrue(true);
    }*/

    //id=null -> database missing 
    public function test_not_agent_in_database()
    {
        // in controller? $validatedData=$request->validate([
        //     'agent'='1|0' //validator
        // ]);
        
        $agent = [
            'id'=>null
        ];
        
        $this
            ->json('GET', 'api/merchant/account/home', $agent)
            ->assertDatabaseMissing('agents', [
                'user_id' => null])
            ->assertJson([
                "message" => "Agent does not exist.",
                "errors" => [
                    "id" => ["The user_id does not exist as an agent."]
                ]
            ]);
    }

    //test if agent_id is in database
    public function test_is_agent_in_database()
    {
        $agent = [
            'user_id'=>3 //X does not exist in database
        ];

        $this
            ->json('GET', 'api/merchant/account/home', $agent)
            ->assertDatabaseHas('agents', [
            'user_id' => '3'
        ]);
    }

    //agent=1 200
    public function test_is_agent()
    {
        // in controller (?)
        // $agentDetails = $request->validate([
        //     'agent_id' => 'required',
        // ]);
        // return response(['message'=>'Agent ID is required']);

        $agent = [
            'id'=>1
        ];

        $this
            ->json('GET', 'api/merchant/account/home', $agent)
            ->assertStatus(200)
            ->assertJsonStructure([
                'name'
            ]);
        
    }

    //send pricing, balance, ratePerHour 404
    //confirm "0 minutes"="0 minutes" for $balance<0
    public function test_for_balance()
    {
        $balanceDetails = [
            'pricing' => 100,
            'balance' => 100, 
            'ratePerHour' => 10
        ];

        $this  
            ->json('GET', 'api/merchant/account/home', $balanceDetails)
            ->assertStatus(404)
            ->assertJson([
                'hour_left'
            ]);
    }

    //problem : want to retrieve data ($hourLeft="0 Minutes" from GetHome.php to compare with expected string
    //current : hardcoded expect and actual string to compare
    public function test_for_less_zero_balance()
    {
        // //in controller (?)
        // // balance < 0 -> will return FAIL
        // $balanceDetails = $request->validate([
        //     'balance' => '> 0 | required',
        // ]);

        // //when FAIL
        // return response(['message' => 'Balance is less than 0']);

        $balance=[
            'balance'=>-1
        ];

        $errorMsg='Actual does not match expected';

        //$hourLeft should be returned from GetHome.php
        $response='0 Minutes';

        $this
            ->json('GET', 'api/merchant/account/home', $balance)
            ->assertStatus(201)
            ->assertJsonStructure([
                'hour_left'
            ]);
    }

    //allowed_to_receive_hourly_parking=0 -> 422
    public function test_to_receive_hourly_parking()
    {
        $permission=[
            'allowed_to_receive_hourly_parking'=>0
        ];

        $this
            ->json('GET', 'api/merchant/account/home', $permission)
            ->assertStatus(422);

    }

    //allowed_to_make_compound=0 -> 422
    public function test_to_make_compound()
    {
        $permission=[
            'allowed_to_make_compound'=>0
        ];

        $this
            ->json('GET', 'api/merchant/account/home', $permission)
            ->assertStatus(422);
    }


    //allowed_to_sell_pass=1 -> 
    public function test_to_give_pass_parking()
    {
        $site=[
            'agent_id'=>1,
            'allowed_to_sell_pass'=>1
        ];

        $this
            ->json('GET', 'api/merchant/account/home', $site)
            ->assertStatus(404);
    }

    //allowed_to_sell_pass=0 404
    public function test_not_give_pass_parking()
    {
        $site=[
            'agent_id'=>1,
            'allowed_to_sell_pass'=>0
        ];

        $this
            ->json('GET', 'api/merchant/account/home', $site)
            ->assertStatus(404);
    }

    //zone_id=null/wrong id -> FAIL 404
    public function test_agent_has_site()
    {
        $site=[
            'agent_id'=>2,
            // 'zone_id'=>null/wrong id ********
        ];

        // at controller
        // $siteDetails = $request->validate([
        //     'agent_id' => 'integer|required',
        //     'zone_id' => 'integer|required'
        // ]);

        // return response(['message'=>'You do not have sites you are in charged of']);

        $this
            ->json('GET', 'api/merchant/account/home', $site)
            ->assertStatus(404)
            ->assertJsonStructure([
                'name',
                'site',
                "message"=>"Site does not exist"
            ]);
    }

    //kiv
    public function test_get_all_return_data()
    {
        $data = [
            'balance' => '100',
            'name' => 'John Doe',
            'version' => '1.1',
            'site' => '',
            'total_collection_today' => '765',
            'is_require_parking_lot' => 'true',
            // 'pbt_logo_url' => $receiptLogo,
            // 'pbt_logo_receipt_url' => $receiptLogoTwo,
            // 'pbt_logo_receipt_parking_url' => $receiptLogoParking,
            // 'pbt_logo_receipt_pass_url' => $receiptLogoPass,
            // 'pbt_logo_receipt_compound_url' => $receiptLogoCompound,
            'agent_status' => '1',
            'hour_left' => '56 Minutes',
        ];

        $errorMsg='Data does not match expected';

        $this
            // ->json('GET', 'api/merchant/account/home', $data,['Accept'=>'application/json'])
            ->assertEquals($data, $actual, $errorMsg)
            ->assertJsonStructure([
                'balance',
                'name',
                'version',
                'site',
                'total_collection_today',
                'is_require_parking_lot',
                // 'pbt_logo_url' => $receiptLogo,
                // 'pbt_logo_receipt_url' => $receiptLogoTwo,
                // 'pbt_logo_receipt_parking_url' => $receiptLogoParking,
                // 'pbt_logo_receipt_pass_url' => $receiptLogoPass,
                // 'pbt_logo_receipt_compound_url' => $receiptLogoCompound,
                'agent_status',
                'hour_left',
            ]);
    }

    //if success login->return expected jsonstructure
    public function test_success_login()
    {
        $userData=[
            'name'=>'',
            'site'=>'',
            'version'=>'',
            'balance'=>'',
            'total_collection_today'=>'',
            'hour_left'=>''
        ];

        $this
            ->json('GET', 'api/merchant/account/home', $userData)
            ->assertStatus(201)
            ->assertJsonStructure([
                'name',
                'site',
                'version',
                'balance',
                'total_collection_today',
                'hour_left'
            ]);
    }

    //if no balance, return error message, agent needs to top up in order to receive all payment
    //expect fail -> return all permission=0 
    public function test_no_balance_no_permission()
    {
        $balance=0;
        
        //by default :
        $permission=[
            'allowed_to_make_compound'=>0,
            'allowed_to_sell_pass_oku'=>0,
            'allowed_to_sell_pass_corporate'=>0,
            'allowed_to_receive_hourly_parking'=>0,
            'total_collection_today'=>0
        ];

        $this
            ->json('GET', 'api/merchant/account/home', $balance, $permission)
            ->assertStatus(404)
            ->assertJsonStructure([
                "balance",
                "permission"=> [
                    'allowed_to_make_compound'=>0,
                    'allowed_to_sell_pass_oku'=>0,
                    'allowed_to_sell_pass_corporate'=>0,
                    'allowed_to_receive_hourly_parking'=>0,
                    'total_collection_today'=>0
                ],
                "message" => "Need to top up balance to start receiving payments"
            ]);
    }

    public function test()
    {

    }

}
