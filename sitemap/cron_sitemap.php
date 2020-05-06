<?php
/**********************************************************
 *          FiletName:cron_sitemap.php                    *
 *          ProjectName:discuzX1.5google��ͼ����          *
 *		    Version:0.1			                          *	
 *          Author:Bugx                                   *
 *          Start:2010-10-26							  *
 ��          End:										  *
 ��	        QQ:28891102									  *
 ��			Email:bugxzhu@gmail.com		       			  *
 ��			Site:http://www.bugx.org					  *
 ��*********************************************************/
// require '../../../source/class/class_core.php';
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}


//error_reporting(E_ERROR);




global $sm_step,$sm_start,$bbs_url,$site_url,$home_url,$group_url,$portal_url,$bbs_page,$portal_page;
global $home_page,$home_url,$group_page,$sitemap_path,$cronid,$last_start_point;

/*�û��Զ������ÿ�ʼ*/
//�����Ķ�����������ɾ����վ��ͼĿ¼�����еĵ�ͼ�ļ��Լ�data��sitemap.log�ļ�����������һ�Ρ�

 $sm_step=5000; //����ִ�д����������Լ���Ҫ�޸�

 $bbs_page="thread"; //bbs�ľ�̬ҳ�����Ĭ��thread-xxx-1-1.html��ʽ

 $portal_page="article";//portal��̬ҳ�����Ĭ��Ϊarticle-xx-1.html��ʽ

 $home_page="space";//�û�������ҳ��̬ҳ�����Ĭ��Ϊspace-uid-xxxxx.html

 $group_page="group"; //Ⱥ�龲̬ҳ�����Ĭ��group-{fid}-{page}.html

 $sitemap_path="/sitemap/";//sitemap��XML�ļ������·�����ļ�����Ҫ�Լ�����

/*�û��Զ������ý���*/



 $sm_start=array('thread_start'=>0,'thread_last_start'=>0,'blog_start'=>0,'blog_last_start'=>0,'group_start'=>0,'group_last_start'=>0,'space_start'=>0,'space_last_start'=>0,'news_start'=>0,'news_last_start'=>0);
 $bbs_url="";
 $site_url=$_G['siteurl'];
 $home_url="";
 $cronid="14";
 if( !is_dir(DISCUZ_ROOT.$sitemap_path))
	{
		echo "{$sitemap_path}������,���ֹ�����Ŀ¼��������д��Ȩ��...<br/>";
		exit;
	}

 if (file_exists(DISCUZ_ROOT."./data/sitemap.log"))
 {
	
	 $sm_file=fopen(DISCUZ_ROOT."./data/sitemap.log","r");
	 $sm_start=unserialize(trim(fgets($sm_file,1024)));
	 
	 fclose($sm_file);

	 if (!$sm_start){
		echo "sitemap.log��ʽ����ȷ,׼������...<br/>";
		 create_new_logfile();
	 }  
 }
 else{
	echo "sitemap.log������,׼������...<br/>";
	create_new_logfile();
 }

//dump($sm_start);

 //��ȡ�ű�����ID
$query= DB::query ("SELECT cronid FROM ".DB::table('common_cron')." where filename='cron_sitemap.php'");
$row = mysqli_fetch_object( $query );
$cronid=$row->cronid;

//��ȡ����
$bbs_url=($_G['setting']['domain']['app']['forum'])?($_G['setting']['domain']['app']['forum']):($_G['setting']['domain']['app']['default']?$_G['setting']['domain']['app']['default']:$_SERVER['HTTP_HOST']);

$home_url=($_G['setting']['domain']['app']['home'])?($_G['setting']['domain']['app']['home']):($_G['setting']['domain']['app']['default']?$_G['setting']['domain']['app']['default']:$_SERVER['HTTP_HOST']);

$group_url=($_G['setting']['domain']['app']['group'])?($_G['setting']['domain']['app']['group']):($_G['setting']['domain']['app']['default']?$_G['setting']['domain']['app']['default']:$_SERVER['HTTP_HOST']);

$portal_url=($_G['setting']['domain']['app']['portal'])?($_G['setting']['domain']['app']['portal']):($_G['setting']['domain']['app']['default']?$_G['setting']['domain']['app']['default']:$_SERVER['HTTP_HOST']);

//��ʽ����sitemap�׶�

$s=$_GET['s'];
//echo $s;
if(!$s){$s="forum";}
switch($s)
{
	//��һ������bbs����sitemap
	case "forum":
		 create_bbs_sitemap();
		 break;
//�ڶ��������Ż�sitemap
	case "protal":
		create_portal_sitemap();
		break;
//������������־sitemap
	case "blog":
		create_blog_sitemap();
		break;

//���Ĳ����ɲ�����ҳsitemap
	case "space":
		create_space_sitemap();
		break;

//���岽������Ȧ�ӵ�ַsitemap
	case "group":
		create_group_sitemap();
		break;
	default:
		echo "ȫ���������";
	
}

create_index_sitemap();


//����bbs��sitemap��

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
		echo "bbs��sitemapȫ��������ɣ���ת���Ż�sitemap���ɹ��̡�<br />";
		echo "<script>location.href='/admin.php?action=misc&operation=cron&run={$cronid}&s=protal'</script>";
	}
}



//�����Ż�sitemap
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
		echo "�Ż�sitemapȫ��������ɣ���ת������sitemap���ɹ��̡�<br />";
		echo "<script>location.href='/admin.php?action=misc&operation=cron&run={$cronid}&s=blog'</script>";
		
	}

}



//������־��sitemap��

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
		echo "��־��sitemapȫ��������ɣ���ת��������ҳsitemap���ɹ��̡�<br />";
		echo "<script>location.href='/admin.php?action=misc&operation=cron&run={$cronid}&s=space'</script>";
	}
}

//�����û���ҳ��sitemap��

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
		echo "�û���ҳ��sitemapȫ��������ɣ���ת��Ⱥ��sitemap���ɹ��̡�<br />";
		echo "<script>location.href='/admin.php?action=misc&operation=cron&run={$cronid}&s=group'</script>";
	}
}

//����group��ҳ��sitemap��

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
		echo "Ⱥ���sitemapȫ��������ɡ�<br />";
	}
}


//����sitemap��������ͼ
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


//������¼��־�ļ�
 function create_new_logfile()
 {
	 global $sm_start;
	 echo "���ڴ���sitemap.log<br/>";
	 $sm_file=fopen(DISCUZ_ROOT."./data/sitemap.log","w");
	// $sm_start=array('thread_start'=>0,'blog_start'=>0,'group_start'=>0,'space_start'=>0,'news_start'=>0);
	 if (fwrite($sm_file, serialize($sm_start)) === FALSE) {
	 echo "����д�뵽�ļ�sitemap.log,����Ȩ��";
	 }
	 fclose($sm_file);
	 echo "����sitemap.log���<br/>";
 }

//update��־

 function update_new_logfile($sm_start)
 {
	 echo "���ڸ���sitemap.log<br/>";
	 $sm_file=fopen(DISCUZ_ROOT."./data/sitemap.log","r+");
	 if (fwrite($sm_file, serialize($sm_start)) === FALSE) {
	 echo "����д�뵽�ļ�sitemap.log,����Ȩ��";
	 }
	 fclose($sm_file);
	 echo "����sitemap.log���<br/>";
 }





 /*
  *������sitemap
  *˵����googlemap
  *Ҫ��PHP>=5.0
  *ʹ�÷�����
  *
  */
	class sitemap{
	
	
	private $items = array();
	private $content="";

	 /**
	 * ���ӽڵ�
	 * @param string $loc  ҳ���������ӵ�ַ
	 * @param date $lastmod  ҳ������޸�ʱ�䣬ISO 8601��ָ����ʱ���ʽ��������
	 * @param string $changefreq  ҳ�����ݸ���Ƶ�ʡ�����������������ĵ��ʹ��⼸����"always", "hourly", "daily", "weekly", "monthly", "yearly"
	 * @param string $priority ������ָ��������������������ӵ�����Ȩ��ֵ����ֵ����0.0 - 1.0֮��
	 * @return  array $items
	 * �÷�������)
	 */
	public function AddItem($loc,$changefreq,$priority,$lastmod)
	{
		$loc=$this->replacestr($loc);
		$this->items[]= array('loc'=>$loc,
						'changefreq'=>$changefreq,
						'priority'=>$priority,
						'lastmod'=>$lastmod );
	}
	
	//�滻loc�����ַ�
	public function replacestr($str)
	{
		str_replace("&","&amp;",$str);
		str_replace("'","&apos;",$str);
		str_replace("\"","&quot;",$str);
		str_replace(">","&gt;",$str);
		str_replace("<","&lt;",$str);
		return $str;
	}
	
	//��ӡ��ʾ
	public function Show()
	{
		if (empty($this->content)) 
		$this->buildSitemap();
       echo($this->content);
	}
	
	 /**
	 * ����sitemap
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
	 * ����sitemap
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
	
	//�����ļ�
	public function SaveToFile($filename)
	{
		$handle = fopen($filename, 'wb');
      if ($handle)
	   {
       fwrite($handle, $this->content);
       fclose($handle);
		echo ("����".$filename."�ɹ�<br />");
	   }
	   else
	   {
		   echo ("����ʧ��");
	   }
	}

	//���캯��
	function __construct()
	{
	
		
	}

	 //��������
	function __destruct()
	 {
		unset($this->items,$this->content);
	 }
}

//��ʽ�����麯��
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