<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/2/7
 * Time: 14:07
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Manage extends Authox_Controller
{
    public function __construct()
    {
        $this->authox_start=true;
        parent::__construct();
    }

    /**
     * 后台管理页面
     * 需登录
     */
    function index()
    {
        $menu=$this->_menu();
        $this->load->view("template/head",
                        array("ac_tittle"=>'后台',
                                "ac_page"=>"manage",
//                                "script"=>array(ADMIN_JS.'controller/manageController.js'),
                                "menu"=>$menu
                        )
                    );
        $this->load->view("manage/index");
        $this->load->view("template/foot");
    }

    function _menu()
    {
        $this->load->helper("array");
        $authorityData=$this->session->userdata("authorityData");
        $this->config->load("ac");
        $temp=$this->config->item("ac_menu");
        $cs = array_no_repeat(array_column($authorityData, 'controller'));
        $menu=array();
        foreach ($cs as $key=>$value)
        {
            if(array_key_exists($value,$temp))
                $menu[$value]=$temp[$value];
        }
        if(!array_key_exists("manage",$menu))
        {
            $menu["manage"]=$temp["manage"];
        }

        return $menu;
    }

    /**
     * 根据用户权限返回用户可见的菜单
     * 需登录
     */
    function menu()
    {
        $menu=$this->_menu();
        echo json_encode(array(
            "status"=>true,
            "message"=>"获取成功",
            "data"=>$menu
        ));
    }

    function system()
    {
        $db="mysql:host=".$this->db->hostname.";dbname=".$this->db->database;
        $connection=new PDO($db,$this->db->username,$this->db->password);
        $provider = \probe\Factory::create();
        $os=array(
            "getOsRelease"=>mb_convert_encoding($provider->getOsRelease(), 'UTF-8', mb_detect_encoding($provider->getOsRelease(), array("ASCII",'UTF-8',"GB2312","GBK",'BIG5'))),
            "getOsType"=>$provider->getOsType(),
            "getOsKernelVersion"=>$provider->getOsKernelVersion(),
            "getArchitecture"=>$provider->getArchitecture(),
            "getDbVersion"=>$provider->getDbVersion($connection),
            "getDbInfo"=>$provider->getDbInfo($connection),
            "getDbType"=>$provider->getDbType($connection),
            "getTotalMem"=>$provider->getTotalMem(),
//            "getFreeMem"=>$provider->getFreeMem(),
//            "getUsedMem"=>$provider->getUsedMem(),
            "getHostname"=>$provider->getHostname(),
            'getCpuCores'=>$provider->getCpuCores(),
            'getCpuPhysicalCore'=>$provider->getCpuPhysicalCores(),
            'getCpuModel'=>$provider->getCpuModel(),
//            'getCpuUsage'=>$provider->getCpuUsage(),
            'getServerIP'=>$_SERVER['SERVER_NAME']."(".$provider->getServerIP().")",
            'getServerSoftware'=>$provider->getServerSoftware(),
            'getPhpVersion'=>$provider->getPhpVersion(),
            'getWebEngine'=>$_SERVER['SERVER_SOFTWARE'],
            'getWebPort'=>$_SERVER['SERVER_PORT'],
            'getWebPath'=>$_SERVER['DOCUMENT_ROOT'] ? str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']) : str_replace('\\', '/', dirname(__FILE__)),
            'getSystemTime'=>DATE_TIME,
            'getLanguage'=>getenv("HTTP_ACCEPT_LANGUAGE"),
            "getDisk"=>"",
            "getDiskFree"=>'',
            "getDiskTotal"=>"",
            'getUploadSize'=>get_cfg_var('upload_max_filesize')
        );

        $os["getOsRelease"]=explode("|",$os["getOsRelease"])[0];
        $os["getDisk"]=explode("/",$os["getWebPath"])[0];
        $os["getDiskFree"]=round(disk_free_space($os["getDisk"])/1024/1024/1024,2)."GB";
        $os["getDiskTotal"]=round(@disk_total_space(".") / (1024 * 1024 * 1024), 2)."GB";
        $os["getTotalMem"]=round($os["getTotalMem"]/1024/1024/1024,0)."GB";
        echo json_encode(array(
            "status"=>true,
            "message"=>"获取成功",
            "data"=>$os
        ));
    }

    function dash()
    {
        function GetWMI($wmi, $strClass, $strValue = array())
        {
            $arrData = array();

            $objWEBM = $wmi->Get($strClass);
            $arrProp = $objWEBM->Properties_;
            $arrWEBMCol = $objWEBM->Instances_();
            foreach ($arrWEBMCol as $objItem) {
                @reset($arrProp);
                $arrInstance = array();
                foreach ($arrProp as $propItem) {
                    eval("\$value = \$objItem->" . $propItem->Name . ";");
                    if (empty($strValue)) {
                        $arrInstance[$propItem->Name] = trim($value);
                    } else {
                        if (in_array($propItem->Name, $strValue)) {
                            $arrInstance[$propItem->Name] = trim($value);
                        }
                    }
                }
                $arrData[] = $arrInstance;
            }
            return $arrData;
        }


        $provider = \probe\Factory::create();

        $objLocator = new COM("WbemScripting.SWbemLocator");
        $wmi = $objLocator->ConnectServer();
        $sysinfo = GetWMI($wmi, "Win32_OperatingSystem", array('TotalVisibleMemorySize', 'FreePhysicalMemory'));
        $res['物理内存'] = $sysinfo[0]['TotalVisibleMemorySize'];
        $res['剩余内存'] = $sysinfo[0]['FreePhysicalMemory'];
        $res['已使用内存'] = $res['物理内存'] - $res['剩余内存'];

        $dash=array(
            'mem'=>array(

                array("name"=>"已使用","value"=>$res['剩余内存']),
                array("name"=>"未使用","value"=>$res['已使用内存']),
            ),
            "cpu"=>array(

                array("name"=>"已使用","value"=>$provider->getCpuUsage()[0]),
                array("name"=>"未使用","value"=>1-$provider->getCpuUsage()[0]),
            ),
        );

        echo json_encode(array(
            "status"=>true,
            "message"=>"获取成功",
            "data"=>$dash
        ));
    }
}