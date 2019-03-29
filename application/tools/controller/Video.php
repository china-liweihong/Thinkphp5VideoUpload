<?php
// +----------------------------------------------------------------------
// | Tptxy [ WE ONLY DO WHAT IS NECESSARY ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://www.vilyun.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 雷小天
// +----------------------------------------------------------------------
namespace app\tools\controller;

use \think\Cache;
use \think\Controller;
use think\Loader;
use think\Db;
use think\Config;
use \think\Cookie;
use \think\Session;
use app\common\controller\ToolsBase;
use app\common\model\Doorcontroller as doorcontrollerModel;
use app\common\model\Recordinout as recordinoutModel;
use think\Exception;

class Video extends ToolsBase{

    private $filepath = './public/uploads/bigfile'; //上传目录
    private $tmpPath; //PHP文件临时目录
    private $blobNum; //第几个文件块
    private $totalBlobNum; //文件块总数
    private $fileName; //文件名

    public function __construct(){
        parent::__construct();
        $this->redis= new \Redis();
        $this->redis->connect('127.0.0.1',6379);

        // $this->tmpPath = $tmpPath;
        // $this->blobNum = $blobNum;
        // $this->totalBlobNum = $totalBlobNum;
        // $this->fileName = $fileName;

        // $this->moveFile();
        // $this->fileMerge();
    }

    //封装好了分片上传视频
    public function uploadvideofile(){
        $tmpPath = $_FILES['file']['tmp_name'];
        $blobNum = $_POST['blob_num'];
        $totalBlobNum = $_POST['total_blob_num'];
        $fileName = $_POST['file_name'];
        $filepath = './public/uploads/bigfile/video';
        Vendor('uploadfile.upload');
        $upload = new \upload($tmpPath,$blobNum,$totalBlobNum,$fileName,$filepath);
        // $upload->apiReturn();
        if($blobNum==$totalBlobNum){//上传完成
        //写入到附件表
        $data = [];
        $data['module'] = 'video';
        $data['filename'] = $fileName;//文件名
        $data['filepath'] = '/public/uploads/bigfile/video/'.$fileName;//文件路径
        $data['fileext'] = 'mp4';//文件后缀
        $data['filesize'] = $_POST['file_size'];//文件大小
        $data['create_time'] = time();//时间
        $data['audit_time'] = time();//时间
        $data['status'] = 1;
        $data['uploadip'] = $this->request->ip();//IP
        $data['user_id'] =  0;
        $data['use'] = 'video_file';//用处
        $id = Db::name('attachment')->insertGetId($data);
        $upload->apiReturn($id);//返回json数据，并返回附件id
        // $addata=array(
        //   'title'=>'视频广告位',
        //   'create_time'=>time(),
        //   'thumb'=>$id,
        //   'status'=>1,
        //   'starttime'=>strtotime(date("Y-m-d"),time()),
        //   'overtime'=>strtotime(date("Y-m-d"),time())+86399,
        //   'is_pay'=>1,
        //   'admin_id'=>2,
        //   'doorcontroller_id'=>6,
        //   'type'=>2,
        //   'adorder_id'=>0,
        // );
        // $slide_id=Db::name('slide')->insertGetId($addata);
      }else{//没有上传完成
        $upload->apiReturn();
      }

    }


}
