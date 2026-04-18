<?php
// 小说制作详情
namespace app\video\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;


class BookListDetail extends Admin {
    
    public function index() 
    {
     

        $map = $this->getMap();
        // if($book_id >  0 ){
        //     // $map["video_id"] = $video_id;
        //     $map[]=["book_id","=", $book_id];
        // }else{
        //     $this->success('video_id错误', url('video/book_list/index'));
        // }
        // 使用动态指定的数据库连接进行查询
        $data_list = DB::connect('translate')->table('ts_book_detail_list')->where($map)
        ->order('time desc')
        ->paginate();

        cookie('ts_book_detail_list', $map);
        
        return ZBuilder::make('table')
            ->setTableName('video/BookListDetailModel',2) // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['index', '序号'],
                ['book_id', '小说关联ID'],
               

                // ['draw_type', '绘画类型','status','',[0=>'sd',1=>'mj',2=>'dall3']],
                ['mp3_status', 'mp3_status','text.edit'],
                ['gpt_status', 'gpt_status','text.edit'],
                ['draw_status', 'draw_status','text.edit'],
                ['video_status', 'video_status','text.edit'],
                ['time', '创建时间'],
                ['text', '文本', 'callback', function($source_text) {
                    // 限制字符串长度为50个字符
                    return mb_strimwidth($source_text, 0, 50, '...');
                }],
                ['scene', '场景词', 'callback', function($source_text) {
                    // 限制字符串长度为50个字符
                    return mb_strimwidth($source_text, 0, 50, '...');
                }],
                ['prompt', '提示词', 'callback', function($source_text) {
                    // 限制字符串长度为50个字符
                    return mb_strimwidth($source_text, 0, 50, '...');
                }],
                
                ['tag_ids', 'tags'],
                ['width', '图片宽'],
                ['heigth', '图片高'],
                ['mp3_url', 'mp3_url', 'image_video'],
                ['duration', '分镜时间'],
                // ['mp3_params', '语音参数'],
            
                ['mp3_params', '语音参数', 'callback', function($source_text) {
                    // 限制字符串长度为50个字符
                    return mb_strimwidth($source_text, 0, 50, '...');
                }],
                ['image_url', 'image_url','img_url'],
                ['image_urls', 'image_urls'],
                ['image_parmas', '作图参数'],
                ['video_url', 'video_url', 'image_video'],
                ['video_url', 'video_url'],
                ['motion', '运动方式'],
                ['scene_status', 'scene_status'],
                ['image_urls', '图集'],
                ['error_msg', 'error_msg'],

                
                
            ])
            ->setSearchArea([  
                ['daterange', 'time', '时间', '', '', ['format' => 'YYYY-MM-DD']],
                ['text', 'id', 'ID'],

                ['text', 'book_id', 'book_id'],
                ['text', 'video_status', 'video_status'],
                ['text', 'mp3_status', 'mp3_status'],
                ['text', 'gpt_status', 'gpt_status'],
                ['text', 'draw_status', 'draw_status'],

                ['text', 'scene_status', 'scene_status'],
                                ['text', 'index', '序号'],
              
            ])
            ->addTopButton('back') // 批量添加顶部按钮
            ->addTopButton('delete',['title'=>'删除']) // 批量添加顶部按钮


            ->setRowList($data_list) // 设置表格数据
            ->setHeight('auto')
             ->addTopButton('redraw_book',[
                'title'=>'重绘小说',
                'class' => 'btn btn-success js-get',
                'icon' => 'fa fa-fw fa-file-excel-o',
                'href' => '/admin.php/video/book_list_detail/redraw_book.html?' . $this->request->query(),
                
            ])
            
            ->fetch(); // 渲染页面
    }

  public  function redraw_book(){


    $ids = input('get.ids');

    if(empty($ids) ){
        $this->error('请勾选需要重绘的书籍');
    }
    // 将ids字符串分割为数组
    $ids_array = explode(',', $ids);
    
    // 查询数据库
    $data_list = DB::connect('translate')->table('ts_book_detail_list')->whereIn('id', $ids_array)->find();
   

 

    $ch = curl_init();
    
    // 准备请求头，假设需要一个'Authorization'头，你可能需要添加它
    $headers = array(
        'Content-Type: application/json'
    );

    // 准备POST数据
    // $data = json_encode(array("cephalon_id" => $machine_ids));
    $postData = array(
            "book_id" => $data_list['book_id']
        );
    $data = json_encode($postData);


    curl_setopt($ch, CURLOPT_URL, "https://ts-api.fyshark.com/api/retry_book_by_id");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $result = curl_exec($ch);
    
    if(curl_errno($ch)){
        // 如果在执行过程中出现错误，可以在这里处理，例如记录日志等
        // 错误信息：curl_error($ch);
        curl_close($ch);
        return null; // 或者返回或抛出一个错误信息
    }

    curl_close($ch);
    // print($result);
    // die();
    $this->success('已重绘', 'index');
    // return json_decode($result, true);
}


}
