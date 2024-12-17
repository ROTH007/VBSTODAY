<?php

namespace App\Http\Controllers;

use App\Exports\MainSheet;
use App\Imports\AdiImport;
use App\Imports\BuilderImport;
use App\Imports\CfoImport;
use App\Imports\TctImport;
use App\Imports\TelecomImport;
use App\Imports\UsersImport;
use App\Models\Builder;
use App\Models\Cfo;
use App\Models\User;
use http\Header;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Facades\Excel as Excel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\HeadingRowImport;

class ImportFileController extends Controller
{  public function verifyData_old_version(Request $request)
{
    ini_set('memory_limit', '1G');
    $total=0;
    $verification = false;
    $duplicateCount = 0;
    $title="";
    $company = $request->input('company');
    $duplicate = array();
    $notDuplicateCount = 0;
    $notDuplicate= array();
    $other = array();
    $builderFile = $request->file("builder");
    $companyFile = $request->file("cfo");
    $validateBuilderHeader = (new HeadingRowImport())->toArray($builderFile);
    $validateCompanyHeader = (new HeadingRowImport())->toArray($companyFile);
    $buildingStoreAsArray = Excel::toArray(new BuilderImport(), $builderFile);
    $companyStoreAsArray = Excel::toArray(new BuilderImport(), $companyFile);
//        dd($validateCompanyHeader);
    $otherData = $companyStoreAsArray[0];
    $notDuplicate = $buildingStoreAsArray[0];
    $selectData = [];
    $companyHeader= array();
    if ($validateBuilderHeader[0][0][0] == "id" && $validateBuilderHeader[0][0][1] == "name" && $validateBuilderHeader[0][0][2] == "active" &&
        $validateBuilderHeader[0][0][3] == "date" && $validateBuilderHeader[0][0][4] == "infrastructure" && $validateBuilderHeader[0][0][5] == "jobs" &&
        $validateBuilderHeader[0][0][6] == "category" && $validateBuilderHeader[0][0][7] == "installation_order") {
        //Insert builder to database

        if ($company == 'cfocn') {
            $title = "CFOCN";
            $selectNeedData = Builder::where('infrastructure',"LIKE","%".$title."%")->get();
            if ($validateCompanyHeader[0][0][0] == "work_order" && $validateCompanyHeader[0][0][1] == "port" &&
                $validateCompanyHeader[0][0][2] == "pos" && $validateCompanyHeader[0][0][3] == "team_install" &&
                $validateCompanyHeader[0][0][4] == "create_time") {
                $cfoStoreAsArray = Excel::toArray(new CfoImport(), $companyFile);
                $selectData = Builder::where('infrastructure', "CFO")->get();
                foreach ($buildingStoreAsArray[0] as $key => $builderData) {
                    foreach ($cfoStoreAsArray[0] as $key2 => $cfoData) {
                        if ($builderData['jobs'] != null) {
                            if ($builderData['jobs'] == $cfoData['Work Order']) {
                                array_push($duplicate, $builderData);
                                // array_push($duplicate, $selectData);
                                unset($notDuplicate[$key]);
//                                        array_push($other, $cfoData);
                                unset($otherData[$key]);
                            }
                        }
                    }
                }

            }
            foreach ($cfoStoreAsArray[0] as $key => $tctData) {
                foreach ($duplicate as $key2 => $company) {
                    if ($company['jobs'] == $cfoData["Work Order"]) {
                        unset($other[$key]);
                    }
                }
            }
            $companyHeader = ['Work Order','PORT','POS','Team Install','Create Time'];
        }
        elseif ($company == 'tct') {
            $title = "TCT";
            if ($validateCompanyHeader[0][0][1] == "tct_cid" && $validateCompanyHeader[0][0][2] == "tct_sid" &&
                $validateCompanyHeader[0][0][3] == "new_circuit_id" && $validateCompanyHeader[0][0][18] == "total_nrc"){
                $tctStoreAsArray = Excel::toArray(new TctImport(), $companyFile);
                $other = $tctStoreAsArray[0];
                foreach ($buildingStoreAsArray[0] as $key => $builderData) {
                    if ($builderData['jobs'] != null) {
                        foreach ($tctStoreAsArray[0] as $key3 => $tctData) {
                            $otherChecker = true;
                            if ($builderData['jobs'] == $tctData["New circuit ID"]) {
                                array_push($duplicate, $builderData);
                                unset($notDuplicate[$key]);
                                unset($otherData[$key]);
//                                        if($otherChecker){
//
//                                        }
                            }
                        }
                    }
                }
                foreach ($tctStoreAsArray[0] as $key => $tctData) {
                    foreach ($duplicate as $key2 => $company) {
                        if ($company['jobs'] == $tctData["New circuit ID"]) {
                            unset($other[$key]);
                        }
                    }
                }
                $companyHeader = ['TCT Id','TCT SID','New circuit ID','Status','Total NRC'];

                //   dd($other);
            }
            else {
                return redirect("/")->with("incorrectCompany", "Invalid Company Column name");
            }
        }
        elseif ($company == 'adi') {
            $title = "ADI";
            if(
                $validateCompanyHeader[0][0][1] == "aid" && $validateCompanyHeader[0][0][2] == "sid" &&
                $validateCompanyHeader[0][0][3] == "customer_name" && $validateCompanyHeader[0][0][4] == "qty"  &&
                $validateCompanyHeader[0][0][5] == "date_start" && $validateCompanyHeader[0][0][6] == "date_to"&&
                $validateCompanyHeader[0][0][7] == "amount" && $validateCompanyHeader[0][0][8] == "vat" &&
                $validateCompanyHeader[0][0][9] == "total_amount" && $validateCompanyHeader[0][0][10] == "invoice_description") {
                $adiStoreAsArray = Excel::toArray(new AdiImport(), $companyFile);
                foreach ($buildingStoreAsArray[0] as $key => $builderData) {
                    if ($builderData['jobs'] != null) {
                        foreach ($adiStoreAsArray[0] as $key4 => $adiData) {
                            if ($builderData['jobs'] == $adiData['AID']) {
                                array_push($duplicate, $builderData);
                                unset($notDuplicate[$key]);
                                unset($otherData[$key]);
                            }else{
                                array_push($other, $builderData);
                            }
                        }
                    }
                }

                foreach ($adiStoreAsArray[0] as $key => $tctData) {
                    foreach ($duplicate as $key2 => $company) {
                        if ($company['jobs'] == $tctData["AID"]) {
                            unset($other[$key]);
                        }
                    }
                }
                $companyHeader = ['AID','SID','Customer Name','Qty','Amount','VAT','Total Amount','Invoice Description','Date Start','Date To'];

                //   dd($other);

            }else{ return redirect("/")->with("incorrectCompany", "Invalid Company Column name");
            }
        }
        elseif ($company == 'telecom') {
            $title = "Telecom";
            if ($validateCompanyHeader[0][0][1] == "account_no" && $validateCompanyHeader[0][0][2] == "id" &&
                $validateCompanyHeader[0][0][3] == "name_customer" && $validateCompanyHeader[0][0][5] == "project" &&
                $validateCompanyHeader[0][0][6] == "issue_date" && $validateCompanyHeader[0][0][7] == "complete_date") {
                $telecomStoreAsArray = Excel::toArray(new TelecomImport(), $companyFile);
                foreach ($buildingStoreAsArray[0] as $key => $builderData) {
                    if ($builderData['jobs'] != null) {
                        foreach ($telecomStoreAsArray[0] as $key5 => $telecom) {

                            if ($builderData['jobs'] == $telecom['ID']) {
                                array_push($duplicate, $builderData);
                                unset($notDuplicate[$key]);
                                unset($otherData[$key]);
                            }else{
                                array_push($other, $builderData);
                            }
                        }
                    }
//                        foreach ($telecomStoreAsArray[0] as $key1 => $tctData) {
//                            foreach ($duplicate as $key2 => $company) {
//                                if ($company['jobs'] == $tctData["ID"]) {
//                                    unset($other[$key1]);
//                                }
//                            }
//                        }
                    $companyHeader = ['Account No','ID','Name Customer','Project','Issue Date','Complete Date'];
                }
            }else{

                return redirect("/")->with("incorrectCompany", "Invalid Company Column name");
            }
        }
    } else {
        return redirect("/")->with("incorrectBuilder", "Invalid Builder Name");
    }
//        dd($duplicate, $storeDuplicateBuilding);
    if($request->input('status') =='active'){
        $fileName = $title." Active List";
    }else{
        $fileName = $title." Terminated List";
    }
    return Excel::download(new MainSheet($duplicate , $notDuplicate, $other, $companyHeader),  "$fileName.xlsx");

}

    public function verifyData(Request $request)
    {
        ini_set('memory_limit', '1G');
        $title="";
        $company = $request->input('company');
        $match = array();
        $notDuplicate= array();
        $notMatch = array();
        $builderFile = $request->file("builder");
        $companyFile = $request->file("cfo");
        $validateBuilderHeader = (new HeadingRowImport())->toArray($builderFile);
        $validateCompanyHeader = (new HeadingRowImport())->toArray($companyFile);
        $buildingStoreAsArray = Excel::toArray(new BuilderImport(), $builderFile);
        $companyStoreAsArray = Excel::toArray(new BuilderImport(), $companyFile);
        $otherData = $companyStoreAsArray[0];
        $selectNeedData = [];
        $notDuplicate = $buildingStoreAsArray[0];
        $companyHeader= array();
        if ($validateBuilderHeader[0][0][0] == "id" && $validateBuilderHeader[0][0][1] == "name" && $validateBuilderHeader[0][0][2] == "active" &&
            $validateBuilderHeader[0][0][3] == "date" && $validateBuilderHeader[0][0][4] == "infrastructure" && $validateBuilderHeader[0][0][5] == "jobs" &&
            $validateBuilderHeader[0][0][6] == "category" && $validateBuilderHeader[0][0][7] == "installation_order") {
            //Insert builder to database
            if(Builder::count() <= 0) {
                $saveBuilder = Excel::import(new BuilderImport(), $builderFile);
            }
                if ($company == 'cfocn') {
                    $title = "CFOCN";
                    $selectNeedData = Builder::where('infrastructure',"LIKE","%".$title."%")->get();
                    $notDuplicate= $selectNeedData->toArray();
                    if ($validateCompanyHeader[0][0][0] == "work_order" && $validateCompanyHeader[0][0][1] == "port" &&
                        $validateCompanyHeader[0][0][2] == "pos" && $validateCompanyHeader[0][0][3] == "team_install" &&
                        $validateCompanyHeader[0][0][4] == "create_time") {
                        $cfoStoreAsArray = Excel::toArray(new CfoImport(), $companyFile);
                        $notMatch = $cfoStoreAsArray[0];
//                            foreach ($cfoStoreAsArray[0] as $key => $cfoData) {
//                                foreach ($buildingStoreAsArray[0] as $key2 => $builderData) {
//                                if ($builderData['jobs'] != null) {
//                                    if ($builderData['jobs'] == $cfoData['Work Order']) {
//                                        array_push($match, $cfoData);
//                                        unset($notDuplicate[$key]);
//                                        unset($notMatch[$key]);
//                                    }
//                                }
//                            }
//                        }
                        $jobsMap = [];
                        foreach ($buildingStoreAsArray[0] as $builderData) {
                            if ($builderData['jobs'] != null) {
                                $jobsMap[$builderData['jobs']] = $builderData;
                            }
                        }

                        // Step 2: Compare $cfoStoreAsArray against the hash map
                        foreach ($cfoStoreAsArray[0] as $key => $cfoData) {
                            if (isset($jobsMap[$cfoData['Work Order']])) {
                                array_push($match, $cfoData);
                                unset($notDuplicate[$key]);
                                unset($notMatch[$key]);
                            }
                        }
                    }

                    $companyHeader = ['Work Order','PORT','POS','Team Install','Create Time'];
                }
                elseif ($company == 'tct') {
                    $title = "TCT";
                    $selectNeedData = Builder::where('infrastructure',"LIKE","%".$title."%")->get();
                    $notDuplicate= $selectNeedData->toArray();
                    if ($validateCompanyHeader[0][0][1] == "tct_cid" && $validateCompanyHeader[0][0][2] == "tct_sid" &&
                        $validateCompanyHeader[0][0][3] == "new_circuit_id" && $validateCompanyHeader[0][0][18] == "total_nrc"){
                        $tctStoreAsArray = Excel::toArray(new TctImport(), $companyFile);
                        $notMatch = $tctStoreAsArray[0];

                        // Step 1: Create a hash map from $buildingStoreAsArray for quick lookup
                        $jobsMap = [];
                        foreach ($buildingStoreAsArray[0] as $builderData) {
                            if ($builderData['jobs'] != null) {
                                $jobsMap[$builderData['jobs']] = $builderData;
                            }
                        }

                        // Step 2: Compare $cfoStoreAsArray against the hash map
                        foreach ($tctStoreAsArray[0] as $key => $tctData) {
                            if (isset($jobsMap[$tctData['New circuit ID']])) {
                                array_push($match, $tctData);
                                unset($notDuplicate[$key]);
                                unset($notMatch[$key]);
                            }
                        }

                        $companyHeader = ['TCT Id','TCT SID','New circuit ID','Status','Total NRC'];
                    }
                    else {
                        return redirect("/")->with("incorrectCompany", "Invalid Company Column name");
                    }
                }
                elseif ($company == 'adi') {
                    $title = "ADI";
                    $selectNeedData = Builder::where('infrastructure',"LIKE","%".$title."%")->get();
                    if(
                        $validateCompanyHeader[0][0][1] == "aid" && $validateCompanyHeader[0][0][2] == "sid" &&
                        $validateCompanyHeader[0][0][3] == "customer_name" && $validateCompanyHeader[0][0][4] == "qty"  &&
                        $validateCompanyHeader[0][0][5] == "date_start" && $validateCompanyHeader[0][0][6] == "date_to"&&
                        $validateCompanyHeader[0][0][7] == "amount" && $validateCompanyHeader[0][0][8] == "vat" &&
                        $validateCompanyHeader[0][0][9] == "total_amount" && $validateCompanyHeader[0][0][10] == "invoice_description") {
                        $adiStoreAsArray = Excel::toArray(new AdiImport(), $companyFile);
                        $notMatch = $adiStoreAsArray[0];
//                        foreach ($adiStoreAsArray[0] as $key => $adiData) {
//                            foreach ($selectNeedData as $key2 => $builderData) {
//                                if ($builderData['jobs'] != null) {
//                                    if ($builderData['jobs'] == $adiData['AID']) {
//                                        array_push($match, $adiData);
//                                        unset($notDuplicate[$key]);
//                                        unset($notMatch[$key]);
//                                    }
//                                }
//                            }
//                        }
                        $jobsMap = [];
                        foreach ($buildingStoreAsArray[0] as $builderData) {
                            if ($builderData['jobs'] != null) {
                                $jobsMap[$builderData['jobs']] = $builderData;
                            }
                        }

                        // Step 2: Compare $cfoStoreAsArray against the hash map
                        foreach ($adiStoreAsArray[0] as $key => $adiData) {
                            if (isset($jobsMap[$adiData['AID']])) {
                                array_push($match, $adiData);
                                unset($notDuplicate[$key]);
                                unset($notMatch[$key]);
                            }
                        }
                        $companyHeader = ['AID','SID','Customer Name','Qty','Amount','VAT','Total Amount','Invoice Description','Date Start','Date To'];
                    }else{ return redirect("/")->with("incorrectCompany", "Invalid Company Column name");
                    }
                }
                elseif ($company == 'telecom') {
                    $title = "Telecom";
                    $selectNeedData = Builder::where('infrastructure', "LIKE", "%" . $title . "%")->get();
                    if ($validateCompanyHeader[0][0][1] == "account_no" && $validateCompanyHeader[0][0][2] == "id" &&
                        $validateCompanyHeader[0][0][3] == "name_customer" && $validateCompanyHeader[0][0][5] == "project" &&
                        $validateCompanyHeader[0][0][6] == "issue_date" && $validateCompanyHeader[0][0][7] == "complete_date") {
                        $telecomStoreAsArray = Excel::toArray(new TelecomImport(), $companyFile);
                        $notMatch = $telecomStoreAsArray[0];

//                        foreach ($telecomStoreAsArray[0] as $key => $telecomData) {
//                                    foreach ($selectNeedData as $key2 => $builderData) {
//                                        if ($builderData['jobs'] != null) {
//                                            if ($builderData['jobs'] == $telecomData['ID']) {
//                                                array_push($match, $telecomData);
//                                                unset($notDuplicate[$key]);
//                                                unset($notMatch[$key]);
//
////                                                array_push($match, $tctData);
////                                                unset($notDuplicate[$key]);
////                                                unset($notMatch[$key]);
//                                            }
//                                        }
//                                    }
//                                }
                        $jobsMap = [];
                        foreach ($buildingStoreAsArray[0] as $builderData) {
                            if ($builderData['jobs'] != null) {
                                $jobsMap[$builderData['jobs']] = $builderData;
                            }
                        }

                        // Step 2: Compare $cfoStoreAsArray against the hash map
                        foreach ($telecomStoreAsArray[0] as $key => $telecomData) {
                            if (isset($jobsMap[$telecomData['ID']])) {
                                array_push($match, $telecomData);
                                unset($notDuplicate[$key]);
                                unset($notMatch[$key]);
                            }
                        }

                        $companyHeader = ['Account No','ID','Name Customer','Project','Issue Date','Complete Date'];

                }else{

                        return redirect("/")->with("incorrectCompany", "Invalid Company Column name");
                    }
                }
            } else {
                return redirect("/")->with("incorrectBuilder", "Invalid Builder Name");
            }
                    $fileName = $title." ".$request->input('status')." List";
        $deleteDataFromDatabase = Builder::truncate();
                    return Excel::download(new MainSheet($match , $notDuplicate, $notMatch, $companyHeader),  "$fileName.xlsx");

    }
//        }

}
