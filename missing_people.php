<?php
    header('Content-Type: application/atom+xml; charset=UTF-8');
    if($_GET['key'] != 'yqNm7FHSwfdRb8nC2653') die('Key required');
?>
<?php echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>"; ?>
<?php
    die();
    require_once('db.php');
    
    function grab_reports($limit='0,50',$sincets='0',$uptots='1500000000',$only_phone=0,$category=array(),$carrierid='0',$actionable=0) {
        $extra_query = '';
        if($only_phone == 1){
            $extra_query .= 'AND sms.number IS NOT NULL ';
        }
        if(count($category) > 0){
            $extra_query .= 'AND (1=2 ';
            foreach($category as $catid){
                $extra_query .= 'OR aid_type LIKE \'%'.$catid.'%\' ';
            }
            $extra_query .= ') ';
        }
        if($carrierid != 0){
            $extra_query .= 'AND sms.carrierid = '.mysql_escape_string($carrierid).' ';
        }
        if($actionable == 1){
            $extra_query .= 'AND actionable = 1 ';
        }
        $person_where = " ((person.status IS NOT NULL AND person.status != '') OR ";
        $person_where .= "person.aid_type LIKE '%6b%' OR person.aid_type LIKE '%4h%') AND ";
        
        $query = "SELECT person.id, person.firstname, person.lastname, person.fullname, person.city, person.department, person.status, person.address, person.lat, person.lon, person.created, person.updated, person.sms, person.ts, person.aid_type, person.notes, person.smsid, person.gender, person.numppl, person.actionable, person.private_notes, sms.date_rec as date_rec, sms.number as phone, sms.message as message, sms.carrierid as carrierid, senderid.senderid as phoneid, carrier.name as carriername FROM person LEFT JOIN sms ON sms.smsid = person.smsid LEFT JOIN senderid ON senderid.number = sms.number LEFT JOIN carrier ON carrier.carrierid = sms.carrierid WHERE $person_where created >= ".mysql_escape_string($sincets)." AND created <= ".mysql_escape_string($uptots)." ".$extra_query." order by created desc ";
        
        #print $query;
        #die();
        
        $sth = mysql_query($query);
        return $sth;
    }
    
    $category = array();
    if(isset($_GET['category'])) $category = explode(',',$_GET['category']);
    
    $limit = '0,50';
    if(isset($_GET['limit'])) $limit = $_GET['limit'];
    
    $sincets = '0';
    if(isset($_GET['sincets'])) $sincets = $_GET['sincets'];
    
    $uptots = '1500000000';
    if(isset($_GET['uptots'])) $uptots = $_GET['uptots'];
    
    $only_phone = 0;
    if(isset($_GET['only_phone'])) $only_phone = $_GET['only_phone'];
    
    $carrierid = '0';
    if(isset($_GET['carrierid'])) $carrierid = $_GET['carrierid'];
    
    $actionable = 0;
    if(isset($_GET['actionable'])) $actionable = $_GET['actionable'];
    
    $sth = grab_reports($limit,$sincets,$uptots,$only_phone,$category,$carrierid,$actionable);
    $rows = array();
    
    $highest_updated_date = 0;
    while($r = mysql_fetch_assoc($sth)) {
        $rows[] = $r;
        if($r['ts'] > $highest_updated_date) $highest_updated_date = $r['ts'];
    }
?>

<feed xmlns="http://www.w3.org/2005/Atom" xmlns:georss="http://www.georss.org/georss">
    <id>"http://4636.ushahidi.com"</id>
   <title>4636.ushahidi.com</title>
   <subtitle>The official database of reports to the 4636 short code in Haiti.</subtitle>
   <link href="http://4636.ushahidi.com"/>
   <author>
      <name>sms://4636</name>
   </author>
   <updated><?php echo str_replace(' ','T',$highest_updated_date).'Z'; ?></updated>
<?php
foreach ($rows as $item) {
    
    $message = preg_replace("/[\x80-\xff]/", '?', $item['message']);
    $notes = preg_replace("/[\x80-\xff]/", '?', $item['notes']);
    $city = preg_replace("/[\x80-\xff]/", '?', $item['city']);
    $address = preg_replace("/[\x80-\xff]/", '?', $item['address']);
    $department = preg_replace("/[\x80-\xff]/", '?', $item['department']);
    $lastname = preg_replace("/[\x80-\xff]/", '?', $item['lastname']);
    $firstname = preg_replace("/[\x80-\xff]/", '?', $item['firstname']);
    $numppl = preg_replace("/[\x80-\xff]/", '?', $item['numppl']);
    $private_notes = preg_replace("/[\x80-\xff]/", '?', $item['private_notes']);
    
    $summary = 'Phone Number:'.$item['phone'].' --- Translator Notes:'.$message.' - '.$notes.'';
    
    $extra = '';
    if($actionable == 1){
        $extra = '<private_notes><![CDATA['.$private_notes.']]></private_notes>';
        $summary .= ' --- Private Notes:'.$private_notes;
    }
    

 
    echo '
        <entry>
        <id>http://4636.ushahidi.com/person.php?id='.$item['id'].'</id>
        <link href="http://4636.ushahidi.com/person.php?id='.$item['id'].'"/>
        <author><name>sms://'.$item['phone'].'</name></author>
        <updated>'.str_replace(' ','T',$item['ts']).'Z</updated>
        <title>'.$firstname.' '.$lastname.' at '.$item['lat'].','.$item['lon'].'</title>
        <sms><![CDATA['.$message.']]></sms>
        <smsrec>'.$item['date_rec'].'</smsrec>
        <phone>'.$item['phone'].'</phone>
        <carrierid>'.$item['carrierid'].'</carrierid>
        <carriername>'.$item['carriername'].'</carriername>
        <phoneid>'.$item['phoneid'].'</phoneid>
        <category term="'.str_replace('-','',$item['aid_type']).'"/>
        <categorization>'.str_replace('-','',$item['aid_type']).'</categorization>
        <actionable>'.$item['actionable'].'</actionable>
        <firstname>'.$firstname.'</firstname>
        <lastname>'.$lastname.'</lastname>
        <gender>'.$item['gender'].'</gender>
        <numppl><![CDATA['.$numppl.']]></numppl>
        <status>'.$item['status'].'</status>
        <address>'.$address.'</address>
        <city>'.$city.'</city>
        <department>'.$department.'</department>
        <summary><![CDATA['.$summary.']]></summary>
        <notes><![CDATA['.$notes.']]></notes>
        '.$extra.'
        <georss:point>'.$item['lat'].' '.$item['lon'].'</georss:point>
    </entry>';
      
}
?>
</feed>
