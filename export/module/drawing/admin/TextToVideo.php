<?php
//文转视频任务记录表
namespace app\drawing\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use think\Db;

class TextToVideo extends Admin {
	
    public function index() 
    {
        $map = $this->getMap();
        //获取cms本身的用户信息
        $user_info =  get_user_info();
        $user_id = is_signin();

        if($user_info["role"] == 2){
         
         $map[]=["cms_id","=", $user_id];   

        }


        $data_list = DB::table('ai_text_to_video')->where($map)
        ->order('time desc')
        ->paginate()->each(function($item, $key){
            // $cnt = Db::table('we_video_album')->where('video_id',$item["id"])->count();
            // $item['has_count'] = $cnt;
            // $item['cms_id'] = $user_info["role"];
            // if($cnt != $item["album_cnt"]){
            //     DB::table("we_video")->where('id',$item["id"])->update(["album_cnt"=>$cnt]);
            // }

            return $item;
        }); 


        cookie('ai_text_to_video', $map);
        
        return ZBuilder::make('table')
            ->setTableName('text_to_video') // 设置数据表名
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['cms_id', '平台用户ID'],
                ['title','文本标题'],
                ['charpid','章节ID'],
                ['mp3_url','mp3链接','image_video'],
                // ['mp3_url','mp3链接'],
                ['mp3_status', 'MP3状态','status','',[0=>'待绘制',1=>'绘制中',2=>'已绘制']],
                ['mp3_status', 'MP3状态','text.edit'],
                
                ['peiyin', '声优'],

                ['mp4_url', 'mp4视频','image_video'],
                ['mp4_status', 'MP4状态','status','',[0=>'待绘制',1=>'绘制中',2=>'已绘制']],
                ['mp4_status', 'mp4状态','text.edit'],
            //    ['status', '拆解状态','status','',[0=>'待绘制',1=>'绘制中',2=>'已绘制']],
                // ['draw_progress', '绘画率'],
                ['comment', '备注'],
                ['time', '创建时间'],
              
               
                ['right_button', '操作', 'btn']
            ])
            // ->hideCheckbox()
            ->setSearchArea([
                
                ['text', 'charpid', '章节ID'],
               
            ])
            ->addRightButton('info',[
                'title'=>'查看分词',
                'icon'  => 'fa fa-fw fa-key',
                'class' => 'btn btn-info btn-rounded',
                // 'href'=>'/admin.php/drawing/text_to_detail/index.html?_s=text_video_id=__id__&_o=text_video_id=eq',
                'href'=>url('drawing/text_to_detail/index',['text_video_id'=>'__id__']),
                // 'href'=>'__href__',
                // 'target'=>'_blank',
            ],false,['style'=>'primary','title' => true,'icon'=>false])

            
            ->addRightButton('edit',[
                'title'=>'编辑文本',
                'class'=>'btn btn-success btn-rounded',
            ],false,['style'=>'primary','title' => true,'icon'=>false]) // 批量添加右侧按钮

            ->setRowList($data_list) // 设置表格数据
            ->setColumnWidth('right_button', 200)

            ->setHeight('auto') 
            ->addTopButtons('add,delete')
           
            ->fetch(); // 渲染页面

    }

public function add() 
     {
        $user_id = is_signin();
        $user_info =  get_user_info();
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            
            if ($user_info["role"] == 2) {
               
            // $select_status = Db::table('ai_text_to_video')->where('mp3_status', '<>', 2)->where('mp4_status', '<>', 2)->where('cms_id', '=', $user_id)->count();
            $select_status = DB::query("select count(*) as cnt from ai_text_to_video where mp3_status<>2 or mp4_status<>2 and cms_id ='".$user_id."';");
            $select_status_cnt=0;
            foreach ($select_status as  $value) {
            $select_status_cnt = $value['cnt'];
        }

            $select_status_all = Db::table('ai_text_to_video')->where("cms_id",$user_id)->count();
            // print_r($select_status_cnt);
            if ($select_status_cnt != 0 && $select_status_all !=0) {
                $this->error('新增失败!需要绘制的计划结束，才能再次新增');
                return;
            } 
            }
            


            $data['cms_id'] = $user_id;
            
            $r = DB::table('ai_text_to_video')->insert($data);
            if ($r) {
                $this->success('新增成功', 'index');
            } else {
                $this->error('新增失败');
            }
        }
       
        // 显示添加页面
        return ZBuilder::make('form')
        
            ->addFormItems([
                // ['text','video_url','视频链接'],
                ['text', 'title', '文本标题'],
                ['text', 'charpid', '章节ID'],
                
                ['select', 'peiyin', '声优','',['zhimiao_emo'=>'知妙_多情感','zhimi_emo'=>'知米_多情感','zhiyan_emo'=>'知燕_多情感','zhibei_emo'=>'知贝_多情感','zhitian_emo'=>'知甜_多情感','xiaoyun'=>'小云','xiaogang'=>'小刚','ruoxi'=>'若兮','siqi'=>'思琪','sijia'=>'思佳','sicheng'=>'思诚','aiqi'=>'艾琪','aijia'=>'艾佳','aicheng'=>'艾诚','aida'=>'艾达','ninger'=>'宁儿','ruilin'=>'瑞琳','siyue'=>'思悦','aiya'=>'艾雅','aixia'=>'艾夏','aimei'=>'艾美','aiyu'=>'艾雨','aiyue'=>'艾悦','aijing'=>'艾婧','xiaomei'=>'小美','aina'=>'艾娜','yina'=>'伊娜','sijing'=>'思婧','sitong'=>'思彤','xiaobei'=>'小北','aitong'=>'艾彤','aiwei'=>'艾薇','aibao'=>'艾宝','shanshan'=>'姗姗','aiyuan'=>'艾媛','aiying'=>'艾颖','aixiang'=>'艾祥','aimo'=>'艾墨','aiye'=>'艾晔','aiting'=>'艾婷','aifan'=>'艾凡','chuangirl'=>'小玥','aishuo'=>'艾硕','aide'=>'艾德','qingqing'=>'青青','cuijie'=>'翠姐','xiaoze'=>'小泽','ainan'=>'艾楠','aihao'=>'艾浩','aiming'=>'艾茗','aixiao'=>'艾笑','aichu'=>'艾厨','aiqian'=>'艾倩','aishu'=>'艾树','airu'=>'艾茹','guijie'=>'柜姐','stella'=>'Stella','stanley'=>'Stanley','kenny'=>'Kenny','rosa'=>'Rosa','farah'=>'Farah','mashu'=>'马树','zhiqi'=>'知琪','zhichu'=>'知厨','xiaoxian'=>'小仙','yuer'=>'悦儿','maoxiaomei'=>'猫小美','zhixiang'=>'知祥','zhijia'=>'知佳','zhinan'=>'知楠','zhiqian'=>'知倩','zhiru'=>'知茹','zhide'=>'知德','zhifei'=>'知飞','aifei'=>'艾飞','yaqun'=>'亚群','qiaowei'=>'巧薇','dahu'=>'大虎','ava'=>'ava','zhilun'=>'知伦','ailun'=>'艾伦','jielidou'=>'杰力豆','zhiwei'=>'知薇','laotie'=>'老铁','laomei'=>'老妹','aikan'=>'艾侃','zhimao'=>'知猫','zhiyuan'=>'知媛','zhigui'=>'知柜','zhiya'=>'知雅','zhiyue'=>'知悦','zhishuo'=>'知硕','zhida'=>'知达','zhistella'=>'知莎'
                ]],  

                ['textarea', 'content', '章节内容'],
                ['textarea', 'chatgpt', 'chatgpt的预设，一问一答，行数要是双数','','USER：你是一个手绘大师，你把我输入的文字提取出能用图片描述出来的人物，物品，环境描述的词
ASSISTANT：好的，请提供内容
USER：“你再忍耐一下，老太婆就快死了，等钱到手了我们再好好庆祝。”
ASSISTANT：1. 一个年轻男子和一个老太婆在一间破旧的房子里，老太婆躺在床上，看起来非常虚弱。\n2. 年轻男子戴着黑色的贝雷帽，穿着破旧的衣服，手里拿着一张纸，上面写着一些数字和名字。\n3.在房间的一角，有一台旧式的电话，电话线从墙上垂下来。
USER：我们约定好了丁克一辈子，老了就上养老院。
ASSISTANT：1. 一对年轻夫妻手牵手站在一片青草地上，蓝天白云下，笑容满面。\n2. 男子戴着黑色帽子，穿着白色T恤和蓝色牛仔裤，女子戴着红色头巾，穿着白色连衣裙。\n3.在夫妻两人的身后，是一座现代化养老院的大楼，楼前有一些老人在晒太阳、散步、聊天。
USER：虽然我们的家庭条件还不错，但是也得让王霞出去工作，有独立生活的能力。
ASSISTANT：1. 一对中年夫妇坐在客厅的沙发上，正面对着一个年轻女孩。\n2. 男子穿着黑色西装，女子穿着白色衬衫和黑色裙子，女孩穿着粉色T恤和牛仔裤。\n3. 客厅的一面墙上挂着一幅画，地上铺着红色的地毯，沙发上有几个靠垫。\n4. 在窗户的背景下，可以看到外面的城市景象，高楼大厦，车流不息。
USER：他本是一介废柴，目不识丁，身无长处，在家乡某洲受到千般鄙夷万种嫌
ASSISTANT：1. 一个年轻男子独自站在一片荒凉的土地上，身穿破旧的衣服，头发凌乱，面容憔悴。\n2. 身后是一座破败的房屋，墙上已经脱落了一块块砖头，屋顶上也有几个洞。
USER：冷艳校花，美女班长，竟一个个倾心与他。
ASSISTANT：1. 在男生的周围，有几个女生围着他，其中一个女生穿着校服，手里拿着一束花，另一个女生穿着白色连衣裙，手里拿着一只小狗。
USER：在一个古老的村庄里，有一个名叫小石的男孩。他是一个善良且热心的孩子，总是乐于帮助别人。然而，小石并不富裕，他的家里只有一个老旧的磨坊，而他的父母都在很小的时候就去世了。
ASSISTANT：1. 一个小男孩站在一座古老的磨坊旁边，他的手里拿着一把铁锤，正在修理磨坊。\n2. 小男孩穿着一件破旧的衬衫和一条灰色的裤子，脚上穿着一双破鞋。\n3. 在小男孩的身后，是一片绿色的田野，田野里有一些正在劳作的农民。\n4. 在远处，可以看到一座古老的村庄，村庄里有一些古老的建筑和一些人们在忙碌着。
USER：在遥远的过去，有一座神奇的山。山上居住着一只名叫月光的白兔。月光是个独特的兔子，他出生时身上散发出淡淡的月光，因此得名月光。
ASSISTANT：1. 一座高耸入云的神秘山峰，山峰上布满了茂密的树林和瀑布。\n2. 在山脚下，有一只白色的兔子，它身上散发着淡淡的月光，显得非常神秘。\n3. 兔子的眼睛非常明亮，它正坐在一块大石头上，四周是一片静谧的景象。\n4. 在远处，可以看到一些山峰和云彩，整个画面充满了神秘和浪漫的气息。
USER：我们打闹了一番，又恢复了以往甜蜜的样子，我看看桌上，那里还放着他帮我切好的水果。
ASSISTANT：1.一个男孩，英俊，宽阔的肩膀，身材高挑，黑色西装，非常帅气，跟一个女孩打闹，然后恢复以往甜蜜的样子。桌上放着他帮女孩切好的水果。'],
                ['text', 'comment', '备注'],
               
            ])
            ->fetch();
    }

     public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            $r = DB::table('ai_text_to_video')->where('id',$id)->update($data);
            if ($r) {
                // 记录行为
                // action_log('link_edit', 'cms_link', $id, UID, $data['title']);
                $this->success('编辑成功', 'index');
            } else {
                $this->error('编辑失败');
            }
        }


        $info = DB::table('ai_text_to_video')->where('id',$id)->find();

        return ZBuilder::make('form')
           
             ->addFormItems([
                 ['text', 'title', '文本标题'],
                ['text', 'charpid', '章节ID'],
                ['select', 'peiyin', '声优','',['zhimiao_emo'=>'知妙_多情感','zhimi_emo'=>'知米_多情感','zhiyan_emo'=>'知燕_多情感','zhibei_emo'=>'知贝_多情感','zhitian_emo'=>'知甜_多情感','xiaoyun'=>'小云','xiaogang'=>'小刚','ruoxi'=>'若兮','siqi'=>'思琪','sijia'=>'思佳','sicheng'=>'思诚','aiqi'=>'艾琪','aijia'=>'艾佳','aicheng'=>'艾诚','aida'=>'艾达','ninger'=>'宁儿','ruilin'=>'瑞琳','siyue'=>'思悦','aiya'=>'艾雅','aixia'=>'艾夏','aimei'=>'艾美','aiyu'=>'艾雨','aiyue'=>'艾悦','aijing'=>'艾婧','xiaomei'=>'小美','aina'=>'艾娜','yina'=>'伊娜','sijing'=>'思婧','sitong'=>'思彤','xiaobei'=>'小北','aitong'=>'艾彤','aiwei'=>'艾薇','aibao'=>'艾宝','shanshan'=>'姗姗','aiyuan'=>'艾媛','aiying'=>'艾颖','aixiang'=>'艾祥','aimo'=>'艾墨','aiye'=>'艾晔','aiting'=>'艾婷','aifan'=>'艾凡','chuangirl'=>'小玥','aishuo'=>'艾硕','aide'=>'艾德','qingqing'=>'青青','cuijie'=>'翠姐','xiaoze'=>'小泽','ainan'=>'艾楠','aihao'=>'艾浩','aiming'=>'艾茗','aixiao'=>'艾笑','aichu'=>'艾厨','aiqian'=>'艾倩','aishu'=>'艾树','airu'=>'艾茹','guijie'=>'柜姐','stella'=>'Stella','stanley'=>'Stanley','kenny'=>'Kenny','rosa'=>'Rosa','farah'=>'Farah','mashu'=>'马树','zhiqi'=>'知琪','zhichu'=>'知厨','xiaoxian'=>'小仙','yuer'=>'悦儿','maoxiaomei'=>'猫小美','zhixiang'=>'知祥','zhijia'=>'知佳','zhinan'=>'知楠','zhiqian'=>'知倩','zhiru'=>'知茹','zhide'=>'知德','zhifei'=>'知飞','aifei'=>'艾飞','yaqun'=>'亚群','qiaowei'=>'巧薇','dahu'=>'大虎','ava'=>'ava','zhilun'=>'知伦','ailun'=>'艾伦','jielidou'=>'杰力豆','zhiwei'=>'知薇','laotie'=>'老铁','laomei'=>'老妹','aikan'=>'艾侃','zhimao'=>'知猫','zhiyuan'=>'知媛','zhigui'=>'知柜','zhiya'=>'知雅','zhiyue'=>'知悦','zhishuo'=>'知硕','zhida'=>'知达','zhistella'=>'知莎'
                ]],  
                ['textarea', 'content', '章节内容'],
                ['textarea', 'chatgpt', 'chatgpt的预设，一问一答，行数要是双数'],
                ['text', 'comment', '备注'],

            ])
          
           
            ->setFormData($info)
            ->fetch();
    }


  
}