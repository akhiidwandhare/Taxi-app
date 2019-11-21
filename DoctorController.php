<?php

namespace App\Http\Controllers;

use Validator;
use Illuminate\Support\Facades\Redirect;
use Auth;
use Illuminate\Support\Facades\Hash;
use DB;
use App\Model\Doctor\UserAvailableDate;
use App\Model\Doctor\UserTime;
use App\Model\Admin\Test;
use App\Model\Admin\Shift;
use App\Model\TokenPatient;
use App\Model\TokenPayments;
use Illuminate\Http\Request;
use App\User;
use App\Model\Admin\Agent;
use App\Model\Admin\City;
use App\Model\Admin\Group;
use App\Model\Admin\Speciality;
use App\Model\Admin\WeburlUser;
use App\Model\Admin\Fee;
use App\Model\Admin\Weburl;
use App\Model\Admin\GroupUser;
use App\Model\Doctor\MrAppointment;

class DoctorController extends Controller {

    public function __construct() {
        $this->middleware('auth');
        $this->middleware('role:Admin');
    }

    public function index() {
        $user = Auth::user();
        $alluser = User::get();
        $Hospitals = User::where('group_id', 4)->get();
        $Doctors = User::where('group_id', 3)->get();
        $totalpatients = TokenPatient::where('status', 1)->get();
        $totalmr = MrAppointment::get();
        $Diagnosis = User::where('group_id', 5)->get();
        $tokenPatient = TokenPatient::get();
        $patientByMonth = TokenPatient::select(DB::raw('count(id) as data'), DB::raw('YEAR(booking_date) year, MONTH(booking_date) month'))
                ->groupby('year', 'month')
                ->get();
        return view('admin.admin', compact('alluser', 'Hospitals', 'Doctors', 'Diagnosis', 'totalmr', 'patientByMonth', 'totalpatients'));
    }

    public function profile() {
        $user = Auth::user();
        $doctor = User::find($user->id);
        $agents = DB::table('agents')
                ->orderBy('createdAt', 'desc')
                ->get();
        $cities = DB::table('cities')
                ->orderBy('createdAt', 'desc')
                ->get();
        $specialities = DB::table('specialities')
                ->orderBy('createdAt', 'desc')
                ->get();
        $successtokenPayments = DB::table('token_payments')
                ->where('pay_status', 's')
                ->get();
        $waitingtokenPayments = DB::table('token_payments')
                ->where('pay_status', 'w')
                ->get();
        $canceltokenPayments = DB::table('token_payments')
                ->where('pay_status', 'c')
                ->get();

        $groups = DB::table('groups')
                ->whereNotNull('registration_fees')
                ->orderBy('createdBy', 'desc')
                ->get();
        return view('admin.profile', compact('agents', 'cities', 'specialities', 'groups', 'doctor', 'user'));
    }

    public function profileProcess(Request $request) {
        $user = Auth::user();

        $name = $request->name;
        $hospitalname = $request->hospitalname;
        $email = $request->email;
        $password = $request->password;
        $gender = $request->gender;
        $experience = $request->experience;
        $mobile = $request->mobile;
        $phone = $request->phone;
        $medical_registration_no = $request->medical_registration_no;
        $degree = $request->degree;
        $consulting_fees = $request->consulting_fees;
        $description = $request->description;
        $bank_account_no = $request->bank_account_no;
        $ifsc_code = $request->ifsc_code;
        $registration_fees = $request->registration_fees;
        $address = $request->address;
        $special_note = $request->special_note;
        $city_id = $request->city_id;
        $booking_type = $request->booking_type;
        $status = $request->status;

        if ($request->id != '') {
            $validator = Validator::make(
                            $request->all(), array(
                        'name' => ['required', 'string', 'max:255'],
                        'hospitalname' => 'required',
                        'email' => 'required',
                        'gender' => 'required',
                        'experience' => 'required',
                        'mobile' => 'required',
                        'phone' => 'required',
                        'medical_registration_no' => 'required',
                        'degree' => 'required',
                        'consulting_fees' => 'required',
                        'description' => 'required',
                        'bank_account_no' => 'required',
                        'ifsc_code' => 'required',
                        'registration_fees' => 'required',
                        'address' => 'required',
                        'special_note' => 'required',
                        'city_id' => 'required',
                        'booking_type' => 'required',
                        'specaility_id' => 'required_if:group_id,==,3',
                        'test_id' => 'required_if:group_id,==,5',
                        'status' => 'required',
                            )
            );
        }
        if ($validator->fails()) {

            return back()->withInput($request->input())->withErrors($validator);
        } else {
            if ($request->speciality_id) {
                $speciality = $request->speciality_id;
                $speciality_id = implode(',', $speciality);
            } elseif ($request->test_id) {
                $test = $request->test_id;
                $test_id = implode(',', $test);
            }
            $doctor = new User;
            $doctor->password = Hash::make($password);
            $imageName = time() . '.' . $request->image->getClientOriginalExtension();
            request()->image->move(public_path('images'), $imageName);
            $doctor->image = $imageName;
            $doctor->group_id = $group_id;
            $doctor->name = $name;
            $doctor->hospitalname = $hospitalname;
            $doctor->gender = $gender;
            if ($request->speciality_id) {
                $doctor->specaility_id = $speciality_id;
            } else {
                $doctor->test_id = $test_id;
            }
            $doctor->experience = $experience;
            $doctor->mobile = $mobile;
            $doctor->phone = $phone;
            $doctor->medical_registration_no = $medical_registration_no;
            $doctor->degree = $degree;
            $doctor->consulting_fees = $consulting_fees;
            $doctor->description = $description;
            $doctor->bank_account_no = $bank_account_no;
            $doctor->ifsc_code = $ifsc_code;
            $doctor->registration_fees = $registration_fees;
            $doctor->address = $address;
            $doctor->special_note = $special_note;
            $doctor->city_id = $city_id;
            $doctor->booking_type = $booking_type;
            $doctor->status = $status;
            $doctor->createdBy = $user->id;

            $doctor->save();

            if ($doctor) {
                return Redirect::to('/Admin/index')->with('flash_success', 'Profile Update sucessfully');
            } else {
                return back()->with('flash_error', 'error');
            }
        }
    }

    public function addDoctor() {
        $specialityUser = [];
        $testUser = [];
        $agents = DB::table('agents')
                ->orderBy('createdAt', 'desc')
                ->get();
        $cities = DB::table('cities')
                ->orderBy('createdAt', 'desc')
                ->get();
        $specialities = DB::table('specialities')
                ->orderBy('createdAt', 'desc')
                ->get();
        $groups = DB::table('groups')
                ->whereNotNull('registration_fees')
                ->orderBy('createdBy', 'desc')
                ->get();
        $testdata = DB::table('testlists')
                ->orderBy('createdAt', 'desc')
                ->get();
        return view('admin.DoctorMaster.adddoctor', compact('agents', 'cities', 'specialities', 'groups', 'testdata', 'specialityUser', 'testUser'));
    }

    public function addDoctorProcess(Request $request) {
        $user = Auth::user();
//echo($request);exit;
        $group_id = $request->group_id;
        $name = $request->name;
        $hospitalname = $request->hospitalname;
        $email = $request->email;
        $password = $request->password;
        $gender = $request->gender;
//       $speciality_id = $request->group_id;
//       echo($group_id);exit;
        $experience = $request->experience;
        $mobile = $request->mobile;
        $phone = $request->phone;
        $medical_registration_no = $request->medical_registration_no;
        $degree = $request->degree;
        $consulting_fees = $request->consulting_fees;
        $agent_id = $request->agent_id;
        $description = $request->description;
        $bank_account_no = $request->bank_account_no;
        $ifsc_code = $request->ifsc_code;
        $registration_fees = $request->registration_fees;
        $address = $request->address;
        $special_note = $request->special_note;
        $city_id = $request->city_id;
        $image = $request->image;
        $booking_type = $request->booking_type;
        $status = $request->status;

        if ($request->id != '') {
            $validator = Validator::make(
                            $request->all(), array(
                        // 'group_id' => 'required',
                        'name' => ['required', 'string', 'max:255'],
                        'hospitalname' => 'required',
                        'email' => 'required',
                        'gender' => 'required',
                        'speciality_id' => 'required_if:group_id,==,3',
                        'test_id' => 'required_if:group_id,==,5',
                        'experience' => 'required',
                        'mobile' => 'required',
                        'phone' => 'required',
                        'medical_registration_no' => 'required',
                        'degree' => 'required',
                        'consulting_fees' => 'required',
                        'agent_id' => 'required',
                        'description' => 'required',
                        'bank_account_no' => 'required',
                        'ifsc_code' => 'required',
                        'registration_fees' => 'required',
                        'address' => 'required',
                        'special_note' => 'required',
                        'city_id' => 'required',
                        'booking_type' => 'required',
                        //'is_agree' => 'required',
                        //'allow_login' => 'required',
                        'status' => 'required',
                            // 'createdBy' => 'required',
                            )
            );
        } else {
            $validator = Validator::make(
                            $request->all(), array(
                        'group_id' => 'required',
                        'name' => ['required', 'string', 'max:255'],
                        'hospitalname' => 'required',
                        'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                        'password' => ['required', 'string', 'min:8'],
                        'gender' => 'required',
                        'speciality_id' => 'required_if:group_id,==,3',
                        'test_id' => 'required_if:group_id,==,5',
                        'experience' => 'required',
                        'mobile' => 'required',
                        'phone' => 'required',
                        'medical_registration_no' => 'required',
                        'degree' => 'required',
                        'consulting_fees' => 'required',
                        'agent_id' => 'required',
                        'description' => 'required',
                        'bank_account_no' => 'required',
                        'ifsc_code' => 'required',
                        'registration_fees' => 'required',
                        'address' => 'required',
                        'city_id' => 'required',
                        'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                        'booking_type' => 'required',
                        // 'allow_login' => 'required',
                        'status' => 'required',
                            )
            );
        }
        if ($validator->fails()) {

            return back()->withInput($request->input())->withErrors($validator);
        } else {
            if ($request->id != '') {
                $doctor = User::find($request->id);
                if ($request->image) {
                    $imageName = time() . '.' . $request->image->getClientOriginalExtension();
                    request()->image->move(public_path('images'), $imageName);
                    $doctor->image = $imageName;
                }
            } else {
                $doctor = new User;
                $doctor->password = Hash::make($password);
                $imageName = time() . '.' . $request->image->getClientOriginalExtension();
                request()->image->move(public_path('images'), $imageName);
                $doctor->image = $imageName;
                $doctor->group_id = $group_id;
            }

            if ($request->group_id == 3) {
                $speciality = $request->speciality_id;
                if (is_array($speciality))
                    $speciality_id = implode(',', $speciality);
                else
                    $speciality_id = $speciality;
            } elseif ($request->group_id == 5) {
                $test = $request->test_id;
                if (is_array($test))
                    $test_id = implode(',', $test);
                else
                    $test_id = $test;
            }
            if ($request->group_id == 3) {
                $doctor->specaility_id = $speciality_id;
            } elseif ($request->group_id == 5) {
                $doctor->test_id = $test_id;
            }
            $doctor->name = $name;
            $doctor->hospitalname = $hospitalname;
            $doctor->email = $email;

            $doctor->gender = $gender;
//            $doctor->specaility_id = $specaility_id;
            $doctor->experience = $experience;
            $doctor->mobile = $mobile;
            $doctor->phone = $phone;
            $doctor->medical_registration_no = $medical_registration_no;
            $doctor->degree = $degree;
            $doctor->consulting_fees = $consulting_fees;
            $doctor->agent_id = $agent_id;
            $doctor->description = $description;
            $doctor->bank_account_no = $bank_account_no;
            $doctor->ifsc_code = $ifsc_code;
            $doctor->registration_fees = $registration_fees;
            $doctor->address = $address;
            $doctor->special_note = $special_note;
            $doctor->city_id = $city_id;

            $doctor->booking_type = $booking_type;
            $doctor->status = $status;
            $doctor->createdBy = $user->id;

            $doctor->save();
            $userKey = $doctor->id;
            if ($doctor) {
                $groupUser = new GroupUser;
                $groupUser->group_id = $doctor->group_id;
                $groupUser->user_id = $userKey;
                $groupUser->save();

                $feedata = new Fee;
                $feedata->doctor_fee = 50;
                $feedata->commission = 10;
                $feedata->total = 60;
                $feedata->user_id = $doctor->id;
                $feedata->save();
                return Redirect::to('/Admin/doctorList')->with('flash_success', 'Doctor Added sucessfully');
            } else {
                echo json_encode($doctor);
                exit;
                return back()->with('flash_error', 'error');
            }
        }
    }

    public function editDoctor(Request $request) {
        $doctor = User::find($request->id);
        $agents = Agent::orderBy('createdAt', 'desc')->get();
        $cities = City::orderBy('createdAt', 'desc')->get();
        $specialities = Speciality::orderBy('createdAt', 'desc')->get();
        $testdata = Test::orderBy('createdAt', 'desc')->get();
        $specialityUser = [];
        $testUser = [];
        if ($doctor->specaility_id) {
            $specialityUsers = Speciality::whereIn('id', explode(',', $doctor->specaility_id))->get('id');
            foreach ($specialityUsers as $specialityUsers1) {
                $specialityUser[] = $specialityUsers1['id'];
            }
        } elseif ($doctor->test_id) {
            $testUsers = Test::whereIn('id', explode(',', $doctor->test_id))->get('id');
            foreach ($testUsers as $testUser1) {
                $testUser[] = $testUser1['id'];
            }
        }
        $groups = Group::whereNotNull('registration_fees')->orderBy('createdAt', 'desc')->get();
        return view('admin.DoctorMaster.adddoctor', compact('doctor', 'agents', 'cities', 'specialities', 'testdata', 'groups', 'testUser', 'specialityUser'));
    }

    public function viewDoctor(Request $request) {
        $date = date('Y-m-d');

        $availableDates = UserAvailableDate::where('user_id', $request->id)->where('available_date', '>=', $date)->with('UserDateData', 'user_test')
                ->orderBy('available_date', 'desc')
                ->get();
        // echo($availableDates);exit;
        return view('admin.DoctorMaster.viewdoctor', compact('availableDates'));
    }

    public function viewAdminSlot(Request $request) {
        $tokenPatients = TokenPatient::where('user_available_date_id', $request->availableDateId)->with('User')
                ->orderBy('booking_date', 'desc')
                ->get();
        // echo($tokenPatient);exit;
        return view('admin.DoctorMaster.viewAdminslots', compact('tokenPatients'));
    }

    public function doctorList(Request $request) {
        $requests = DB::table('users')
                ->whereIn('group_id', [3, 4, 5])
                ->orderBy('createdAt', 'desc')
                ->paginate(10);
        return view('admin.DoctorMaster.doctorlist')->with('requests', $requests);
    }

    public function getStatusValue(Request $request) {
        $status_value = $request->status;
        $date_id = $request->availableDateId;
        //echo($status_value);
        //echo($date_id);
        $availableDates = UserAvailableDate::where('availableDateId', $request->availableDateId)
                ->first();
//         echo($availableDates);

        if ($status_value == 0) {
            $availableDates->status = 1;
        } elseif ($status_value == 1) {
            $availableDates->status = 0;
        }
        $availableDates->save();

        $html = "";
        if ($availableDates->status == 1) {
            $html .= '<a class="change_satus m-badge m-badge--success" data-id="1" data-value="' . $availableDates->availableDateId . '" href="#">Active</a>';
        } elseif ($availableDates->status == 0) {
            $html .= '<a class="change_satus m-badge m-badge--warning" data-id="0" data-value="' . $availableDates->availableDateId . '" href="#">Inactive</a>';
        }
        echo($html);
        exit;
    }

    public function getUserStatus(Request $request) {
        $status_value = $request->status;
        $user_id = $request->user_Id;

        $userStatus = User::where('id', $request->user_Id)
                ->first();

        if ($status_value == 0) {
            $userStatus->status = 1;
        } elseif ($status_value == 1) {
            $userStatus->status = 0;
        }
        $userStatus->save();

        $html = "";
        if ($userStatus->status == 1) {
            $html .= '<a class="change_satus m-badge m-badge--success" data-id="1" data-value="' . $userStatus->id . '" href="#">Active</a>';
        } elseif ($userStatus->status == 0) {
            $html .= '<a class="change_satus m-badge m-badge--warning" data-id="0" data-value="' . $userStatus->id . '" href="#">Inactive</a>';
        }
        echo($html);
        exit;
    }

    public function addAdminPatient(Request $request) {
        $shiftlist = Shift::get();
        $id = $request->availableDateId;

        $userTimes = TokenPatient::with(['tokenavailable_date.user_test', 'User'])->where('user_available_date_id', $id)->get();
        //$tests = Test::whereIn('id', explode(',', $userTimes[0]->tokenavailable_date->test_id))->orderBy('id', 'asc')->get();

        if ($userTimes[0]->token_data != Null) {

            return view('admin.DoctorMaster.addAdminToken', compact('userTimes'));
        } elseif ($userTimes[0]->slot_data != Null) {

//            echo($userTimes[0]->tokenavailable_date);
//            exit;
            return view('admin.DoctorMaster.addAdminSlot', compact('userTimes', 'shiftlist'));
        }
    }

    public function addAdminslotProcess(Request $request) {
        // echo($request->test_id);exit;
        $admin = Auth::user();
        $str = $request->slot_data;
        $parts = explode("|", $str);

        $user_id = $request->user_id;
        $availableDate_id = $request->date_id;
        $slot_time_id = $request->group_id;
        $booking_date = $request->sel_date;
        $shift_id = $request->shift_id;

        $open_time = $request->open_time;
        $close_time = $request->close_time;
        $time_slot = $request->time_slot;
        if ($request->test_id) {
            $test_id = $request->test_id;
        } else {
            $test_id = 0;
        }
        $slot_data = $parts[0];
        $txnNo = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
        $name = $request->name;
        $mobile = $request->mobile;
        $email = $request->email;
        $id = $request->id;
        $tokenPatient = TokenPatient::find($id);
      
        if ($id != '') {
       } else {
            $tokenPatient = new TokenPatient;
         }
        
        $tokenPatient->unique_user_id = $txnNo;
        $tokenPatient->user_id = $user_id;
        $tokenPatient->user_available_date_id = $availableDate_id;
        $tokenPatient->user_time_id = $slot_time_id;
        $tokenPatient->booking_date = $booking_date;
        if ($test_id) {
            $tokenPatient->test_id = $test_id;
        }

        $tokenPatient->shift_id = $shift_id;
        $tokenPatient->slot_data = $slot_data;
        $tokenPatient->name = $name;
        $tokenPatient->mobile = $mobile;
        $tokenPatient->email = $email;
        $tokenPatient->status = 1;
        $tokenPatient->unique_user_id = $txnNo;
        $tokenPatient->check_status = 0;
        $tokenPatient->admin_status = 1;
        $tokenPatient->createdBy = $admin->id;
        $tokenPatient->save();

        $tokenPatient->save();
        $amount = 0;
        $tokenPayments = new TokenPayments;
//print_r($tokenPatient);

        $tokenPayments->patient_id = $tokenPatient->id;
        $tokenPayments->payment_txnid = $txnNo;
        $tokenPayments->payment_amount = $amount;
        $tokenPayments->createdBy = $admin->id;
        $tokenPayments->status = 1;
        $tokenPayments->pay_status = 's';
        $tokenPayments->save();
        if ($tokenPatient) {
            return Redirect::to('/Admin/viewAdminSlot/' . $availableDate_id)->with('flash_success', 'Doctor Added sucessfully');
        } else {
            return back()->with('flash_error', 'error');
        }
    }

    public function addAdminTokenProcess(Request $request) {
        $admin = Auth::user();

        $user_id = $request->user_id;
        $availableDate_id = $request->date_id;

        $booking_date = $request->sel_date;
        $token_data_id = $request->token_data_id;
        if ($request->test_id) {
            $test_id = $request->test_id;
        } else {
            $test_id = 0;
        }
        $name = $request->name;
        $mobile = $request->mobile;
        $email = $request->email;
        // print_r($request->token_data_id);exit;
        $tokenPatient = TokenPatient::find($token_data_id);
        $newTokenPatient = $tokenPatient->token_data;
        if ($tokenPatient->id != '' && $tokenPatient->status === 0) {
            
        } else {
            $tokenPatient = new TokenPatient;
        }

        $tokenPatient->user_id = $user_id;
        $tokenPatient->user_available_date_id = $availableDate_id;

        $tokenPatient->booking_date = $booking_date;
        if ($test_id) {
            $tokenPatient->test_id = $test_id;
        }
        $tokenPatient->token_data = $newTokenPatient;
        $tokenPatient->name = $name;
        $tokenPatient->mobile = $mobile;
        $tokenPatient->email = $email;
        $tokenPatient->status = 1;
        $tokenPatient->check_status = 0;
        $tokenPatient->admin_status = 1;
        $tokenPatient->createdBy = $admin->id;
        $tokenPatient->save();
        if ($tokenPatient) {
            return Redirect::to('/Admin/viewAdminSlot/' . $availableDate_id)->with('flash_success', 'Token Added Sucessfully');
        } else {
            return back()->with('flash_error', 'error');
        }
    }

    public function getShift(Request $request) {
        $date_id = $request->date_id;
        $shift_id = $request->shift_id;
        $test_id = $request->test_id;
        //echo($test_id);exit;
        $userTimes = TokenPatient::where('shift_id', $shift_id)->where('test_id', $test_id)->where('user_available_date_id', $date_id)
                ->get();
        $html = "";


        $html .= '<div class = "form-group m-form__group">';

        $html .= '<label for = "Open Time">';
        $html .= 'OpenTime';
        $html .= '</label>';
        $html .= '<select class = "form-control m-input m-input--square" name = "slot_data" id = "slot_data">';
        $html .= ' <option value ="">Please Select Slot</option>';
        foreach ($userTimes as $userTime) {
            $html .= '<option id="' . $userTime->id . '" value = "' . $userTime->slot_data . '|' . $userTime->id . '">' . $userTime->slot_data . '</option>';
        }
        $html .= ' </select>';
        $html .= '<input type="hidden" id="myid"  value="" />';
        $html .= '</div>';

        echo($html);
        exit;
    }

    public function addfee() {
        $users = User::get();
        return view('admin.FeeMaster.addfee', compact('users'));
    }

    public function addFeeProcess(Request $request) {
        $admin = Auth::user();

        $user_id = $request->user_id;
        $doctor_fee = $request->doctor_fee;
        $commission = $request->commission;
        $total = $request->total;
        $createdBy = $admin->id;

        if ($request->id != '') {
            $validator = Validator::make(
                            $request->all(), array(
                        'doctor_fee' => 'required',
                        'commission' => 'required',
                        'total' => 'required',
                            )
            );
        } else {
            $validator = Validator::make(
                            $request->all(), array(
                        'doctor_fee' => 'required',
                        'commission' => 'required',
                        'total' => 'required',
                            )
            );
        }
        if ($validator->fails()) {
            // $error_messages = implode(',', $validator->messages()->all());
            return back()->withInput($request->input())->withErrors($validator);
        } else {
            if ($request->id != '') {
                $fee = Fee::find($request->id);
            } else {
                $fee = new Fee;
            }


            $fee->user_id = $user_id;
            $fee->doctor_fee = $doctor_fee;
            $fee->commission = $commission;
            $fee->total = $total;
            $fee->createdBy = $createdBy;

            $fee->save();
            if ($fee) {
                return Redirect::to('/Admin/feeList')->with('flash_success', 'Fee Added sucessfully');
            } else {
                return back()->with('flash_error', 'error')->withInput($request);
            }
        }
    }

    public function feeList(Request $request) {
        $requests = Fee::with('fee_user')
                ->orderBy('createdAt', 'asc')
                ->paginate(10);
        //echo($requests[3]->fee_user);exit;
        return view('admin.FeeMaster.feelist', compact('requests'));
    }

    public function editFee(Request $request) {
        $fee = Fee::find($request->id);
        $users = User::get();
        return view('admin.FeeMaster.addfee', compact('fee', 'users'));
    }

    public function addUrl() {
        $userUrllist = [];
        $web = DB::table('weburls')
                ->orderBy('createdAt', 'desc')
                ->get();

        $user = User::whereIn('group_id', [3, 5])->where('id', '!=', 2)->get();
        return view('admin.WebUrl.addUrl', compact('web', 'user', 'userUrllist'));
    }

    public function addurlProcess(Request $request) {
        $user = Auth::user();
        if ($request->id != '') {
            $validator = Validator::make(
                            $request->all(), array(
                        'URL' => 'required',
                        'uniqueid' => 'required',
                        'user_id' => 'required',
                            )
            );
        } else {
            $validator = Validator::make(
                            $request->all(), array(
                        'URL' => 'required',
                        'uniqueid' => 'required|unique:weburls',
                        'user_id' => 'required',
                            )
            );
        }
        if ($validator->fails()) {
            $error_messages = implode(',', $validator->messages()->all());
            return back()->withInput($request->input())->withErrors($validator)->with('flash_errors', $error_messages);
        } else {
            if ($request->id != '') {
                $web = WebUrl::find($request->id);
                $webUrlUsers = WeburlUser::where('weburl_id', $web->id)->delete();
                $Urlname = $web->weburlname;
                $value = $web->uniqueid;
            } else {
                $Urlname = $request->URL;
                $value = $request->uniqueid;

                $web = new WebUrl;
            }
            $doctors = $request->user_id;

            $web->weburlname = $Urlname;
            $web->uniqueid = $value;
            $web->createdBy = $user->id;
            $web->save();

            foreach ($doctors as $user_data) {
                // echo($user_data);exit;
                $webUser = new WeburlUser;
                $webUser->user_id = $user_data;
                $webUser->weburl_id = $web->id;
                $webUser->createdBy = $user->id;
                $webUser->save();
            }
            if ($web) {
                return Redirect::to('/Admin/urlList')->with('flash_success', 'City Added sucessfully');
            } else {
                return back()->with('flash_error', 'error');
            }
        }
    }

    public function urlList(Request $request) {
        $url = Weburl::orderBy('createdAt', 'desc')
                ->paginate(10);
        return view('admin.WebUrl.urlList', compact('url'));
    }

    public function editurl(Request $request) {
        $userUrllist = [];
        $url = WebUrl::find($request->id);
        $user = User::get();
        $userUrllists = WeburlUser::where('weburl_id', $url->id)->get('user_id');
//        echo($userUrllists);exit;
        foreach ($userUrllists as $userUrllist1) {
            $userUrllist[] = $userUrllist1['user_id'];
        }
        return view('admin.WebUrl.addUrl', compact('url', 'user', 'userUrllist'));
    }

    public function deleteurl($id) {
        WebUrl::find($id)->delete();
        return Redirect::to('/Admin/urlList');
    }

    public function getStatusUrl(Request $request) {
        $status_value = $request->status;
        $url_id = $request->urlId;
        //echo($status_value);
        //echo($date_id);
        $urlRow = WebUrl::where('id', $url_id)
                ->first();
//         echo($availableDates);

        if ($status_value == 0) {
            $urlRow->status = 1;
        } elseif ($status_value == 1) {
            $urlRow->status = 0;
        }
        $urlRow->save();

        $html = "";
        if ($urlRow->status == 1) {
            $html .= '<a class="change_satus m-badge m-badge--success" data-id="1" data-value="' . $urlRow->id . '" href="#">Active</a>';
        } elseif ($urlRow->status == 0) {
            $html .= '<a class="change_satus m-badge m-badge--warning" data-id="0" data-value="' . $urlRow->id . '" href="#">Inactive</a>';
        }
        echo($html);
        exit;
    }

}
