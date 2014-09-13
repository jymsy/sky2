<?php
namespace Sky\db;
use Sky\db\DBCommand;


$starttime=microtime(true);

echo "<pre>";

function autoLoadClass($className){
	echo "AUTO LOAD ",$className,PHP_EOL;
	require_once '..'.str_replace("\\", "/", substr($className, 3)).".php";
}

spl_autoload_register("\Sky\db\autoLoadClass");

require_once 'testConfig.php';
//ConnectionPool::loadConfig(require("mConfig.php"));////###########
/**
 * @property int id
 * @property string content
 * @property int status
 * @property string author
 * @property string email
 * @property string url
 * @property int post_id
 * @method self joinPost
 * @author huanghw
 *
 */
class Comment extends ActiveRecord{
	protected static $tableName="tbl_comment";
	protected static $primeKey=array("id");
}

class Post extends ActiveRecord{
	protected static $tableName="tbl_post";
}

var_dump($comment=Comment::find(array(),array(
	"where"=>"content like :sKey",
	"bind"=>array("sKey"=>"%test%")
)));

// (new Comment())
// 	->joinPost("title,content","status=2")
// 	->fetchData("content AS comment,author",array("limit"=>5,"order"=>"id desc"));

// (new Query("Comment","content AS comment,author"))
// 	->joinPost("title,content","status=2")
// 	->fetchData(array("limit"=>5,"order"=>"id desc"));

// Comment::fetch("content AS comment,author",array(
// 	"limit"=>5,
// 	"order"=>"id desc",
// 	"join"=>array(
// 		array("Post","select"=>"title,content","where"=>"status=2")
// 	)
// ));
$cmd=new DBCommand("tbl_comment AS c",array("token"=>"TVOS"));
$data=$cmd
	->select("content AS comment,author","c")
	->group("c.id")
	->having("c.id>0")
	->order("c.id desc")
	->limit(1)
	->offset(0)
	->where("c.content like :skey")
	->join("tbl_post AS p","p.id=c.post_id")
	->select("*","p")
	->bind(array("skey"=>"%test%"))
	->toList();
var_dump($data);

// var_dump((new DBCommand("tbl_comment"))
// 	->insert(array(
// 		"content"=>"My Test Comment",
// 		"status"=>1,
// 		"create_time"=>time(),
// 		"author"=>"test",
// 		"email"=>"a@b.com",
// 		"post_id"=>123
// 	))
// 	->exec());

// var_dump((new DBCommand("tbl_comment"))
// 	->where("id=3")
// 	->delete()
// 	->exec());

// (new DBCommand("tbl_comment"))
// 	->where("id=4")
// 	->update(array(
// 		"url"=>date("YmdHis")
// 	))
// 	->exec();

//DBCommand::create("UPDATE tbl_comment SET `url`='abc' WHERE id=4 LIMIT 1;")->exec();

// DBCommand::create(
// "SELECT c.`content` AS comment,c.`author`,p.`title`,p.`content` 
// 	From `tbl_comment` AS c 
// 	left join `tbl_post` AS p on p.`id`=c.`post_id` 
// 	where p.`status`=2
// 	order by c.`id` desc 
// 	limit 5;")->toList();

// echo "$comment->id, $comment->content, $comment->email, $comment->status", PHP_EOL;
// $comment->url="url:".date("YmdHis");
// $comment->save();

// Comment::insert(array(
// 	"content"=>time(),
// 	"author"=>"abc",
// 	"status"=>1,
// 	"email"=>"xxx@aaa",
// 	//"post_id"=>1
// ));

/**
 * 更新id=5的Comment记录的content
 */
Comment::update(array(
	"content"=>"Update@".time(),
	"id"=>5
));

// $comment=Comment::find();//最后一条评论
// $post=$comment->Post();//获取评论的文章
// $post->loadComment(5);//加载文章的5条评论
// $post->lastComment()->content;//获取最后一条评论内容
// $post->firstComment()->content;//获取第一条评论内容
// $post->nextComment()->content;//获取下一条评论内容
// $post->currentComment()->content=HeXieHua($post->currentComment()->content);//屏蔽敏感词
// $post->currentComment()->status=2;//审核通过该评论
// $post->currentComment()->save();//保存修改

echo "Max Mem:",memory_get_peak_usage()," Current Mem:",memory_get_usage()," Time:",(microtime(true)-$starttime)*1000,"ms",PHP_EOL;
echo "</pre>";