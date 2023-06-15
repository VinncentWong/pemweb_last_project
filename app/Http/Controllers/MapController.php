<?php

namespace App\Http\Controllers;

use App\Models\History;
use App\Models\Instruction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MapController extends Controller
{
    public function map(){
        return view("map");
    }

    public function saveHistoryData(Request $request){
        try{
            DB::beginTransaction();
            $input = $request->all();
            $historyInput = $input["history"];
            $history = new History;
            $history->start_location = $historyInput["startLocation"];
            $history->destination = $historyInput["destination"];
            $history->total_time = $historyInput["totalTime"];
            $history->total_distance = $historyInput["totalDistance"];
            $history->save();
            $lastInsertId = $history->getKey();
    
            $instructionInputs = $input["instruction"];
            foreach($instructionInputs as $instructionInput){
                $instruction = new Instruction;
                $instruction->history_id = $lastInsertId;
                $instruction->instruction = $instructionInput;
                $instruction->save();
            }
            DB::commit();
            return [
                "status" => 201,
                "message" => "sukses menyimpan data history"
            ];
        } catch(\Exception $e){
            DB::rollBack();
            return [
                "status" => 500,
                "message" => $e->getMessage()
            ];
        }
    }

    public function getHistoryData($id){
        $resultInstruction = Instruction::where('history_id', $id)->get();
        $resultHistory = History::where('id', $id)->get();
        return [
            "status" => 200,
            "message" => "sukses mendapatkan data history",
            "data" => [
                "history" => $resultHistory,
                "instruction" => $resultInstruction
            ]
        ];
    }
}
