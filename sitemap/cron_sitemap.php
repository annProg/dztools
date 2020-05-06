<?php
/**********************************************************
 *          FiletName:cron_sitemap.php                    *
 *          ProjectName:discuzX1.5google地图生成          *
 *		    Version:0.1			                          *	
 *          Author:Bugx                                   *
 *          Start:2010-10-26							  *
 ×          End:										  *
 ×	        QQ:28891102									  *
 ×			Email:bugxzhu@gmail.com		       			  *
 ×			Site:http://www.bugx.org					  *
 ×*********************************************************/
// require '../../../source/class/class_core.php';
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}


//error_reporting(E_ERROR);




global $sm_step,$sm_start,$bbs_url,$site_url,$home_url,$group_url,$portal_url,$bbs_page,$portal_page;
global $home_page,$home_url,$group_page,$sitemap_path,$cronid,$last_start_point;

/*用户自定义配置开始*/
//如果你改动过参数，请删除网站地图目录下所有的地图文件以及data下sitemap.log文件。重新生成一次。

 $sm_step=5000; //单次执行次数，根据自己需要修改

 $bbs_page="thread"; //bbs的静态页面规则，默认thread-xxx-1-1.html格式

 $portal_page="article";//portal静态页面规则，默认为article-xx-1.html格式

 $home_page="space";//用户个人主页静态页面规则，默认为space-uid-xxxxx.html

 $group_page="group"; //群组静态页面规则，默认group-{fid}-{page}.html

 $sitemap_path="/sitemap/";//sitemap的XML文件保存的路径，文件夹需要自己建立

/*用户自定义配置结束*/



 $sm_start=array('thread_start'=>0,'thread_last_start'=>0,'blog_start'=>0,'blog_last_start'=>0,'group_start'=>0,'group_last_start'=>0,'space_start'=>0,'space_last_start'=>0,'news_start'=>0,'news_last_start'=>0);
 $bbs_url="";
 $site_url=$_G['siteurl'];
 $home_url="";
 $cronid="14";
 if( !is_dir(DISCUZ_ROOT.$sitemap_path))
	{
		echo "{$sitemap_path}不存在,请手工创建目录，并赋予写入权限...<br/>";
		exit;
	}

 if (file_exists(DISCUZ_ROOT."./data/sitemap.log"))
 {
	
	 $sm_file=fopen(DISCUZ_ROOT."./data/sitemap.log","r");
	 $sm_start=unserialize(trim(fgets($sm_file,1024)));
	 
	 fclose($sm_file);

	 if (!$sm_start){
		echo "sitemap.log格式不正确,准备创建...<br/>";
		 create_new_logfile();
	 }  
 }
 else{
	echo "sitemap.log不存在,准备创建...<br/>";
	create_new_logfile();
 }

//dump($sm_start);

 //获取脚本任务ID
$query= DB::query ("SELECT cronid FROM ".DB::table('common_cron')." where filename='cron_sitemap.php'");
$row = mysqli_fetch_object( $query );
$cronid=$row->cronid;

//获取域名
$bbs_url=($_G['setting']['domain']['app']['forum'])?($_G['setting']['domain']['app']['forum']):($_G['setting']['domain']['app']['default']?$_G['setting']['domain']['app']['default']:$_SERVER['HTTP_HOST']);

$home_url=($_G['setting']['domain']['app']['home'])?($_G['setting']['domain']['app']['home']):($_G['setting']['domain']['app']['default']?$_G['setting']['domain']['app']['default']:$_SERVER['HTTP_HOST']);

$group_url=($_G['setting']['domain']['app']['group'])?($_G['setting']['domain']['app']['group']):($_G['setting']['domain']['app']['default']?$_G['setting']['domain']['app']['default']:$_SERVER['HTTP_HOST']);

$portal_url=($_G['setting']['domain']['app']['portal'])?($_G['setting']['domain']['app']['portal']):($_G['setting']['domain']['app']['default']?$_G['setting']['domain']['app']['default']:$_SERVER['HTTP_HOST']);

//正式处理sitemap阶段

$s=$_GET['s'];
//echo $s;
if(!$s){$s="forum";}
switch($s)
{
	//第一步生成bbs帖子sitemap
	case "forum":
		 create_bbs_sitemap();
		 break;
//第二步生成门户sitemap
	case "protal":
		create_portal_sitemap();
		break;
//第三步生成日志sitemap
	case "blog":
		create_blog_sitemap();
		break;

//第四步生成博客首页sitemap
	case "space":
		create_space_sitemap();
		break;

//第五步，生成圈子地址sitemap
	case "group":
		create_group_sitemap();
		break;
	default:
		echo "全部生成完毕";
	
}

create_index_sitemap();


//创建bbs的sitemap；

function create_bbs_sitemap()
{
	global $sm_start,$sm_step,$bbs_url,$bbs_page,$sitemap_path,$cronid;

	$query = DB::query("SELECT tid FROM ".DB::table('forum_thread')." where displayorder >=0 ORDER BY tid desc LIMIT 0,1");
	$row = mysqli_fetch_object( $query );
	$max_tid=$row->tid;

	if ($max_tid >$sm_start['thread_start'])
	{
		if($sm_start['thread_start']>$sm_start['thread_last_start'])
		{
			@unlink(DISCUZ_ROOT.$sitemap_path."bbs_sitemap_".$sm_start['thread_start'].".xml");
		}
		//create_index_sitemap();


	$query= DB::query ("SELECT tid, lastpost FROM ".DB::table('forum_thread')." where tid>".$sm_start['thread_last_start']." and displayorder >=0 ORDER BY tid ASC LIMIT 0,"."$sm_step");
	
	$bbs_sitemap=new sitemap();

	$line=0;
    while( $row = DB::fetch( $query ))
	{	
		//dump($row);
		$postdate=Date('c',$row['lastpost']);
		$loc="https://".$bbs_url."/".$bbs_page."-".$row['tid']."-1-1.html";
		$bbs_sitemap->AddItem($loc,"daily","0.8",$postdate);
		$line++;
		$log_tid=trim($row[tid]);
		if ($line==$sm_step){$sm_start['thread_last_start']=$log_tid;}

	}
	if (is_numeric($log_tid))
		{
		$sm_start['thread_start']=$log_tid;
		update_new_logfile($sm_start);
		$bbs_sitemap->buildSitemap();
		$bbs_sitemap->SaveToFile(DISCUZ_ROOT.$sitemap_path."bbs_sitemap_".$log_tid.".xml");
		echo "<script>location.href='/admin.php?action=misc&operation=cron&run={$cronid}&s=forum'</script>";
		}
		unset($bbs_sitemap);	
	}
	else
	{
		echo "bbs的sitemap全部生成完成，跳转至门户sitemap生成过程。<br />";
		echo "<script>location.href='/admin.php?action=misc&operation=cron&run={$cronid}&s=protal'</script>";
	}
}



//创建门户sitemap
function create_portal_sitemap()
{
	global $sm_start,$sm_step,$portal_url,$portal_page,$sitemap_path,$cronid;
	
	$query = DB::query("SELECT aid FROM ".DB::table('portal_article_title')." where status=0 ORDER BY aid desc LIMIT 0,1");
	$row = mysqli_fetch_object( $query );
	$max_tid=$row->aid;

	if ($max_tid >$sm_start['news_start'])
	{
		if($sm_start['news_start']>$sm_start['news_last_start'])
		{
			@unlink(DISCUZ_ROOT.$sitemap_path."portal_sitemap_".$sm_start['news_start'].".xml");
		}
		//create_index_sitemap();



	$query= DB::query ("SELECT aid, dateline FROM ".DB::table('portal_article_title')." where aid > ".$sm_start['news_last_start']." AND status =0 ORDER BY aid ASC LIMIT 0,"."$sm_step");
	$portal_sitemap=new sitemap();
	$line=0;
	while ($row = DB::fetch($query))
	{
		$postdate=Date('c',$row['dateline']);
		$loc="https://".$portal_url."/".$portal_page."-".$row['aid']."-1.html";
		$portal_sitemap->AddItem($loc,"weekly","0.8",$postdate);
		$line++;
		$log_tid=trim($row[aid]);
		if ($line==$sm_step){$sm_start['news_last_start']=$log_tid;}
	}
	if (is_numeric($log_tid))
	{
		$sm_start['news_start']=$log_tid;
		update_new_logfile($sm_start);
		$portal_sitemap->buildSitemap();
		$portal_sitemap->SaveToFile(DISCUZ_ROOT.$sitemap_path."portal_sitemap_".$log_tid.".xml");
		echo "<script>location.href='/admin.php?action=misc&operation=cron&run={$cronid}&s=protal'</script>";
		
		}
	unset($portal_sitemap);	
    }
	else
	{
		echo "门户sitemap全部生成完成，跳转至博客sitemap生成过程。<br />";
		echo "<script>location.href='/admin.php?action=misc&operation=cron&run={$cronid}&s=blog'</script>";
		
	}

}



//创建日志的sitemap；

function create_blog_sitemap()
{
	global $sm_start,$sm_step,$home_url,$home_page,$sitemap_path,$cronid;

	$query = DB::query("SELECT blogid FROM ".DB::table('home_blog')." where status =0 ORDER BY blogid desc LIMIT 0,1");
	$row = mysqli_fetch_object( $query );
	$max_tid=$row->blogid;

	if ($max_tid >$sm_start['blog_start'])
	{
		if($sm_start['blog_start']>$sm_start['blog_last_start'])
		{
			@unlink(DISCUZ_ROOT.$sitemap_path."blog_sitemap_".$sm_start['blog_start'].".xml");
		}
		//create_index_sitemap();


	$query= DB::query ("SELECT blogid,uid, dateline FROM ".DB::table('home_blog')." where blogid>".$sm_start['blog_last_start']." and status=0 ORDER BY blogid ASC LIMIT 0,"."$sm_step");
	
	$blog_sitemap=new sitemap();

	$line=0;
    while( $row = DB::fetch( $query ))
	{	
		//dump($row);
		$postdate=Date('c',$row['dateline']);
		$loc="https://".$home_url."/blog-".$row['uid']."-".$row['blogid'].".html";//blog-216163-84654.html
		$blog_sitemap->AddItem($loc,"daily","0.8",$postdate);
		$line++;
		$log_tid=trim($row['blogid']);
		if ($line==$sm_step){$sm_start['blog_last_start']=$log_tid;}

	}
	if (is_numeric($log_tid))
		{
		$sm_start['blog_start']=$log_tid;
		update_new_logfile($sm_start);
		$blog_sitemap->buildSitemap();
		$blog_sitemap->SaveToFile(DISCUZ_ROOT.$sitemap_path."blog_sitemap_".$log_tid.".xml");
		echo "<script>location.href='/admin.php?action=misc&operation=cron&run={$cronid}&s=blog'</script>";
		}
		unset($bbs_sitemap);	
	}
	else
	{
		echo "日志的sitemap全部生成完成，跳转至个人主页sitemap生成过程。<br />";
		echo "<script>location.href='/admin.php?action=misc&operation=cron&run={$cronid}&s=space'</script>";
	}
}

//创建用户主页的sitemap；

function create_space_sitemap()
{
	global $sm_start,$sm_step,$home_url,$home_page,$sitemap_path,$cronid;

	$query = DB::query("SELECT uid FROM ".DB::table('common_member')." where status =0 ORDER BY uid desc LIMIT 0,1");
	$row = mysqli_fetch_object( $query );
	$max_tid=$row->uid;

	if ($max_tid >$sm_start['space_start'])
	{
		if($sm_start['space_start']>$sm_start['space_last_start'])
		{
			@unlink(DISCUZ_ROOT.$sitemap_path."space_sitemap_".$sm_start['space_start'].".xml");
		}
		//create_index_sitemap();


	$query= DB::query ("SELECT uid,regdate FROM ".DB::table('common_member')." where uid>".$sm_start['space_last_start']." and status=0 ORDER BY uid ASC LIMIT 0,"."$sm_step");
	
	$space_sitemap=new sitemap();

	$line=0;
    while( $row = DB::fetch( $query ))
	{	
		//dump($row);
		$postdate=Date('c',$row['regdate']);
		$loc="https://".$home_url."/".$home_page.'-uid-'.$row['uid'].".html";//space-uid-2.html
		$space_sitemap->AddItem($loc,"daily","0.8",$postdate);
		$line++;
		$log_tid=trim($row['uid']);
		if ($line==$sm_step){$sm_start['space_last_start']=$log_tid;}

	}
	if (is_numeric($log_tid))
		{
		$sm_start['space_start']=$log_tid;
		update_new_logfile($sm_start);
		$space_sitemap->buildSitemap();
		$space_sitemap->SaveToFile(DISCUZ_ROOT.$sitemap_path."space_sitemap_".$log_tid.".xml");
		echo "<script>location.href='/admin.php?action=misc&operation=cron&run={$cronid}&s=space'</script>";
		}
		unset($space_sitemap);	
	}
	else
	{
		echo "用户主页的sitemap全部生成完成，跳转至群组sitemap生成过程。<br />";
		echo "<script>location.href='/admin.php?action=misc&operation=cron&run={$cronid}&s=group'</script>";
	}
}

//创建group主页的sitemap；

function create_group_sitemap()
{
	global $sm_start,$sm_step,$group_url,$group_page,$sitemap_path,$cronid;

	$query = DB::query("SELECT fid FROM ".DB::table('forum_forum')." where status =3 and type='sub' ORDER BY fid desc LIMIT 0,1");
	$row = mysqli_fetch_object( $query );
	$max_tid=$row->fid;

	if ($max_tid >$sm_start['group_start'])
	{
		if($sm_start['group_start']>$sm_start['group_last_start'])
		{
			@unlink(DISCUZ_ROOT.$sitemap_path."group_sitemap_".$sm_start['group_start'].".xml");
		}
		//create_index_sitemap();


	$query= DB::query ("SELECT fid FROM ".DB::table('forum_forum')." where fid >".$sm_start['group_last_start']." and status=3 and type='sub' ORDER BY fid ASC LIMIT 0,"."$sm_step");
	
	$group_sitemap=new sitemap();

	$line=0;
    while( $row = DB::fetch( $query ))
	{	
		//dump($row);
		$postdate=Date('c',time());
		$loc="https://".$group_url."/".$group_page.'-'.$row['fid']."-1.html";//group-{fid}-{page}.html
		$group_sitemap->AddItem($loc,"hourly","0.8",$postdate);
		$line++;
		$log_tid=trim($row['fid']);
		if ($line==$sm_step){$sm_start['group_last_start']=$log_tid;}

	}
	if (is_numeric($log_tid))
		{
		$sm_start['group_start']=$log_tid;
		update_new_logfile($sm_start);
		$group_sitemap->buildSitemap();
		$group_sitemap->SaveToFile(DISCUZ_ROOT.$sitemap_path."group_sitemap_".$log_tid.".xml");
		echo "<script>location.href='/admin.php?action=misc&operation=cron&run={$cronid}&s=group'</script>";
		}
		unset($group_sitemap);	
	}
	else
	{
		echo "群组的sitemap全部生成完成。<br />";
	}
}


//创建sitemap的索引地图
function create_index_sitemap()
{
	global $sitemap_path,$bbs_url,$site_url,$home_url,$group_url,$portal_url;
	$arrfiles=sdir(DISCUZ_ROOT.$sitemap_path);
	$index_sitemap_portal=new sitemap();
	$index_sitemap_forum=new sitemap();
	$index_sitemap_home=new sitemap();
	$index_sitemap_group=new sitemap();
	
	$postdate=Date('c',time());
	
	foreach($arrfiles as $key=>$value)
	{
		if (strstr($arrfiles[$key],"_sitemap_"))
		{
			if (strstr($arrfiles[$key],"portal_"))
			{
				$loc_portal="https://".$portal_url.'/'.$arrfiles[$key];
				$index_sitemap_portal->AddItem($loc_portal,"hourly","0.8",$postdate);
			}
			elseif (strstr($arrfiles[$key],"bbs_"))
			{
				$loc_forum="https://".$bbs_url.'/'.$arrfiles[$key];
				$index_sitemap_forum->AddItem($loc_forum,"hourly","0.8",$postdate);
			}
			elseif (strstr($arrfiles[$key],"blog_"))
			{
				$loc_home="https://".$home_url.'/'.$arrfiles[$key];
				$index_sitemap_home->AddItem($loc_home,"hourly","0.8",$postdate);
			}
			elseif (strstr($arrfiles[$key],"space_"))
			{
				$loc_home="https://".$home_url.'/'.$arrfiles[$key];
				$index_sitemap_home->AddItem($loc_home,"hourly","0.8",$postdate);
			}
			elseif (strstr($arrfiles[$key],"group_"))
			{
				$loc_group="https://".$group_url.'/'.$arrfiles[$key];
				$index_sitemap_group->AddItem($loc_group,"hourly","0.8",$postdate);
			}
			
			
		}

	}
	$index_sitemap_portal->buildSitemapIndex();
	$index_sitemap_forum->buildSitemapIndex();
	$index_sitemap_home->buildSitemapIndex();
	$index_sitemap_group->buildSitemapIndex();

	$index_sitemap_portal->SaveToFile(DISCUZ_ROOT.$sitemap_path."portal_sitemap.xml");
	$index_sitemap_forum->SaveToFile(DISCUZ_ROOT.$sitemap_path."forum_sitemap.xml");
	$index_sitemap_home->SaveToFile(DISCUZ_ROOT.$sitemap_path."home_sitemap.xml");
	$index_sitemap_group->SaveToFile(DISCUZ_ROOT.$sitemap_path."group_sitemap.xml");
	unset($index_sitemap_portal,$index_sitemap_forum,$index_sitemap_home,$index_sitemap_group);
	
}


//创建纪录日志文件
 function create_new_logfile()
 {
	 global $sm_start;
	 echo "正在创建sitemap.log<br/>";
	 $sm_file=fopen(DISCUZ_ROOT."./data/sitemap.log","w");
	// $sm_start=array('thread_start'=>0,'blog_start'=>0,'group_start'=>0,'space_start'=>0,'news_start'=>0);
	 if (fwrite($sm_file, serialize($sm_start)) === FALSE) {
	 echo "不能写入到文件sitemap.log,请检查权限";
	 }
	 fclose($sm_file);
	 echo "创建sitemap.log完成<br/>";
 }

//update日志

 function update_new_logfile($sm_start)
 {
	 echo "正在更新sitemap.log<br/>";
	 $sm_file=fopen(DISCUZ_ROOT."./data/sitemap.log","r+");
	 if (fwrite($sm_file, serialize($sm_start)) === FALSE) {
	 echo "不能写入到文件sitemap.log,请检查权限";
	 }
	 fclose($sm_file);
	 echo "更新sitemap.log完成<br/>";
 }





 /*
  *类名：sitemap
  *说明：googlemap
  *要求PHP>=5.0
  *使用方法：
  *
  */
	class sitemap{
	
	
	private $items = array();
	private $content="";

	 /**
	 * 增加节点
	 * @param string $loc  页面永久链接地址
	 * @param date $lastmod  页面最后修改时间，ISO 8601中指定的时间格式进行描述
	 * @param string $changefreq  页面内容更新频率。这里可以用来描述的单词共这几个："always", "hourly", "daily", "weekly", "monthly", "yearly"
	 * @param string $priority 是用来指定此链接相对于其他链接的优先权比值。此值定于0.0 - 1.0之间
	 * @return  array $items
	 * 用法举例：)
	 */
	public function AddItem($loc,$changefreq,$priority,$lastmod)
	{
		$loc=$this->replacestr($loc);
		$this->items[]= array('loc'=>$loc,
						'changefreq'=>$changefreq,
						'priority'=>$priority,
						'lastmod'=>$lastmod );
	}
	
	//替换loc特殊字符
	public function replacestr($str)
	{
		str_replace("&","&amp;",$str);
		str_replace("'","&apos;",$str);
		str_replace("\"","&quot;",$str);
		str_replace(">","&gt;",$str);
		str_replace("<","&lt;",$str);
		return $str;
	}
	
	//打印显示
	public function Show()
	{
		if (empty($this->content)) 
		$this->buildSitemap();
       echo($this->content);
	}
	
	 /**
	 * 生成sitemap
	 */
	public function buildSitemap()
	{
		$str="<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		$str .="<urlset xmlns=\"http://www.google.com/schemas/sitemap/0.9\">\n";
		

		 for ($i=0;$i<count($this->items);$i++) 
			{
				$str .= "<url>\n";
				$str .= "<loc>{$this->items[$i]['loc']}</loc>\n";
				$str .= "<lastmod>{$this->items[$i]['lastmod']}</lastmod>\n";
				$str .= "<changefreq>{$this->items[$i]['changefreq']}</changefreq>\n";
				$str .= "<priority>{$this->items[$i]['priority']}</priority>\n";
				$str .="</url>\n";

			}
			$str .= "</urlset>";

			$this->content = $str ;
	}

		 /**
	 * 生成sitemap
	 */
	public function buildSitemapIndex()
	{
		$str="<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		$str .="<sitemapindex  xmlns=\"http://www.google.com/schemas/sitemap/0.9\">\n";
		

		 for ($i=0;$i<count($this->items);$i++) 
			{
				$str .= "<sitemap>\n";
				$str .= "<loc>{$this->items[$i]['loc']}</loc>\n";
				$str .= "<lastmod>{$this->items[$i]['lastmod']}</lastmod>\n";
				$str .="</sitemap>\n";

			}
			$str .= "</sitemapindex >";

			$this->content = $str ;
	}
	
	//保存文件
	public function SaveToFile($filename)
	{
		$handle = fopen($filename, 'wb');
      if ($handle)
	   {
       fwrite($handle, $this->content);
       fclose($handle);
		echo ("创建".$filename."成功<br />");
	   }
	   else
	   {
		   echo ("创建失败");
	   }
	}

	//构造函数
	function __construct()
	{
	
		
	}

	 //析构函数
	function __destruct()
	 {
		unset($this->items,$this->content);
	 }
}

//格式化数组函数
 function dump($vars, $label = '', $return = false) {
 if (ini_get('html_errors')) {
    $content = "<pre>\n";
    if ($label != '') {
            $content .= "<strong>{$label} :</strong>\n";
      }
        $content .= htmlspecialchars(print_r($vars, true));
       $content .= "\n</pre>\n";
     } else {
        $content = $label . " :\n" . print_r($vars, true);
     }
    if ($return) { return $content; }
       echo $content;
     return null;
 }
    function sdir($dir)
  {
   $dh  = opendir($dir);
   while (false !== ($filename = readdir($dh))) {
      $files[] = $filename;
    }
   
    sort($files);
    rsort($files);
    return $files;

 }
?>