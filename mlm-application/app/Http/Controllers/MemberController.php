<?php

namespace App\Http\Controllers;

use Nette\Utils\Json;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class MemberController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $allMember = Member::all();

        $chartData = array_map(function ($data) {
            return [
                'id' => $data['id'],
                'pid' => $data['parent_id'],
                'name' => $data['name']
            ];
        }, $allMember->toArray());

        $chartDataJson = Json::encode($chartData);

        return View("dashboard", compact('allMember', 'chartDataJson'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $requestBody = $request->collect();

        $member = new Member();
        $member->name = $requestBody['name'];
        $member->parent_id = $requestBody['parent_id'];
        $member->save();

        return $member->id;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $requestBody = $request->collect();
        
        $memberData = Member::whereId($requestBody['member_id'])->first();
        $newParentData = Member::whereId($requestBody['parent_id'])->first();

        if ($memberData['id'] == $newParentData['parent_id']) {
            return Response::json(array(
                'code'      =>  404,
                'message'   =>  "Parent Merupakan member"
            ), 404);  ;
        }

        $member = Member::find($requestBody['member_id']);
        $member->parent_id = $newParentData['id'];
        $member->save();
    }

    public function calculateBonus(int $id): string
    {
        $bonusFromMmemberLevelOne = 0;
        $bonusFromMmemberLevelTwo = 0;

        $listMemberLevelOne = Member::where('parent_id', $id)->get();

       if (count($listMemberLevelOne) >= 1) {
        $listMemberLevelOneArray = $listMemberLevelOne->toArray();
        $bonusFromMmemberLevelOne = count($listMemberLevelOne);

        $listParentIdLevelOne = array_map(function ($data) {
            return $data['id'];
        }, $listMemberLevelOneArray);

        $listMemberLevelTwo = Member::whereIn('parent_id', $listParentIdLevelOne)->get();

        if (count($listMemberLevelTwo) >= 1) {
            $bonusFromMmemberLevelTwo = count($listMemberLevelTwo) * 0.5;
        }

        $totalBonus = $bonusFromMmemberLevelOne + $bonusFromMmemberLevelTwo;

        return $totalBonus;

       } else {
            return 0;
       }
    }
}
