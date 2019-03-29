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


namespace app\admin\controller;

use \think\Controller;
use think\Db;
use \think\Session;
use app\admin\controller\Permissions;
use app\common\model\Slide as slideModel;
use app\common\model\Doorcontroller as doorcontrollerModel;
class Slide extends Permissions
{
    public function index()
    {
        $model = new slideModel();

        $post = $this->request->param();
        if (isset($post['keywords']) and !empty($post['keywords'])) {
            $where['title'] = ['like', '%' . $post['keywords'] . '%'];
        }

        if (isset($post['status']) and ($post['status'] == 1 or $post['status'] === '0')) {
            $where['status'] = $post['status'];
        }

        if(isset($post['create_time']) and !empty($post['create_time'])) {
            $min_time = strtotime($post['create_time']);
            $max_time = $min_time + 24 * 60 * 60;
            $where['create_time'] = [['>=',$min_time],['<=',$max_time]];
        }
        //根据管理员查询数据，超级管理员不用过滤
        if( Session::get('admin')!=1){
          $where['admin_id'] = Session::get('admin');
        }else{
          if (isset($post['admin_id']) and $post['admin_id'] > 0) {
              $where['admin_id'] = $post['admin_id'];
          }else{
              $where['admin_id'] = array('gt','0');
          }

        }
        $slide = empty($where) ? $model->order('create_time desc')->paginate(20) : $model->where($where)->order('create_time desc')->paginate(20,false,['query'=>$this->request->param()]);
        //添加最后修改人的name
        foreach ($slide as $key => $value) {
            $address = Db::name('doorcontroller')->where('id',$value['doorcontroller_id'])->value('address');
            $slide[$key]['address'] = getNameByAddress($address,Session::get('admin'),$value['doorcontroller_id']);
        }
        $this->assign('slide',$slide);
        return $this->fetch();
    }


    public function publish()
    {
      $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
      $model = new slideModel();
      $doorcontrollerModel = new doorcontrollerModel();
      //根据管理员查询数据，超级管理员不用过滤
      if( Session::get('admin')!=1){
        $where['admin_id'] = Session::get('admin');
      }else{
        $where['admin_id'] = array('gt','0');
      }
      //是正常添加操作
      if($id > 0) {
          //是修改操作
          if($this->request->isPost()) {
            //是提交操作
            $post = $this->request->post();
            //验证  唯一规则： 表名，字段名，排除主键值，主键名
                $validate = new \think\Validate([
                    ['title', 'require', '标题不能为空'],
                    ['type', 'require', '请选择广告类型'],
                    ['doorcontroller_id', 'require', '请选择设备'],
                    ['starttime', 'require', '请选择开始日期'],
                    ['overtime', 'require', '请选择结束日期'],
                ]);
                //验证部分数据合法性
                if (!$validate->check($post)) {
                    $this->error('提交失败：' . $validate->getError());
                }
                //验证菜单是否存在
                $article = $model->where('id',$id)->find();
                if(empty($article)) {
                  return $this->error('id不正确');
                }
                  //设置修改人
                  $post['edit_admin_id'] = Session::get('admin');
                  //起始日期
                  $post['starttime']= ($post['starttime']!='')?strtotime($post['starttime']):time();
                  //截止日期
                  $post['overtime']= ($post['overtime']!='')?(strtotime($post['overtime'])+86399):time();
                if(false == $model->allowField(true)->save($post,['id'=>$id])) {
                  return $this->error('修改失败');
                } else {
                      addlog($model->id);//写入日志
                  return $this->success('修改成功','admin/slide/index');
                }
          } else {
            //非提交操作
            $doorcontrollers = $doorcontrollerModel->where($where)->where('type','<>',1)->select();
            $this->assign('doorcontrollers',$doorcontrollers);
            $chat = $model->where('id',$id)->find();
            if(!empty($chat)) {
              $this->assign('slide',$chat);
              return $this->fetch();
            } else {
              return $this->error('id不正确');
            }
          }
        } else {
          //是新增操作
          if($this->request->isPost()) {
            //是提交操作
            $post = $this->request->post();
            // die(var_dump($post));
            //验证  唯一规则： 表名，字段名，排除主键值，主键名
                $validate = new \think\Validate([
                    ['title', 'require', '标题不能为空'],
                    ['type', 'require', '请选择广告类型'],
                    ['doorcontroller_id', 'require', '请选择设备'],
                    ['starttime', 'require', '请选择开始日期'],
                    ['overtime', 'require', '请选择结束日期'],
                ]);
                //验证部分数据合法性
                if (!$validate->check($post)) {
                    $this->error('提交失败：' . $validate->getError());
                }
                //如果是视频，要验证视频是否上传完成
                if($post['type']==2&&!isset($post['thumb'])){
                  $validate = new \think\Validate([
                      ['thumb', 'require', '请等待视频上传完成'],
                  ]);
                  if (!$validate->check($post)) {
                      $this->error('提交失败：' . $validate->getError());
                  }
                }
                  //设置创建人
                  $post['admin_id'] = Session::get('admin');
                  //设置修改人
                  $post['edit_admin_id'] = $post['admin_id'];
                  //设置添加时间
                  $post['create_time'] = time();
                  //起始日期
                  $post['starttime']= ($post['starttime']!='')?strtotime($post['starttime']):time();
                  //截止日期
                  $post['overtime']= ($post['overtime']!='')?(strtotime($post['overtime'])+86399):time();
                  //截止日期
                  $post['is_pay'] = 1;
                if(false == $model->allowField(true)->save($post)) {
                  return $this->error('添加失败');
                } else {
                      addlog($model->id);//写入日志
                  return $this->success('添加成功','admin/slide/index');
                }
          } else {
            $doorcontrollers = $doorcontrollerModel->where($where)->where('type','<>',1)->field('id,areainfo_id,address,admin_id')->select();
            $this->assign('doorcontrollers',$doorcontrollers);
            return $this->fetch();
          }
        }
    }


    


    
    
}
