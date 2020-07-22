<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public static $parameter_query = array("healthcare-iot-or-health-iot-or-healthiot" => '("healthcare IoT" OR "health IoT" OR "healthIoT")',
                                     "internet-of-medical-things-or-internet-of-healthcare-things-or-iomt" => '("Internet of Medical Things" OR "Internet of healthcare things" OR "IoMT")',
                                     "internet-of-things-and-health" => '("Internet of Things" and "*Health*")',
                                     "internet-of-things-and-healthcare" => '("Internet of Things" and "*Healthcare*")',
                                     "internet-of-things-and-medical" => '("Internet of Things" and "Medical")',
                                     "medical-iot-or-iot-medical" => '("Medical IoT" OR "IoT Medical")',
                                     "internet-of-things-and-care" => '("internet of things" AND "*care*")');

}
