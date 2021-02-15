<?php
//服务器插件
function Airline_svm_ConfigOptions() {
  $cid = mac_url_get(1);
  $server_id = get_query_val("产品","服务器组",array("id" => $cid));
  $svm_api_addr = "https://".get_query_val("服务器表","ip",array("id" => $server_id)).":5656/api/admin/command.php";
  $svm_api_id = get_query_val("服务器表","用户名",array("id" => $server_id));
  $svm_api_key = decrypt(get_query_val("服务器表","密码",array("id" => $server_id)));
  $svm_api_raddr = get_query_val("服务器表","哈希密码",array("id" => $server_id));
  $vtype = get_query_val("产品","配置选项1",array("id" => $cid));
  $rt = json_decode(svm_curl($svm_api_addr,"id=$svm_api_id&key=$svm_api_key&action=listnodes&type=$vtype&rdtype=json",$svm_api_raddr),true);
  if($rt) {
    if($rt['statusmsg'] == "Invalid ipaddress") $nodelist = "请确认IP白名单设置是否正确";
    if($rt['statusmsg'] == "API account inactive") $nodelist = "API账户已被禁用 请确认配置是否正确";
    if($rt['statusmsg'] == "Invalid id or key") $nodelist = "API ID或KEY输入错误 请确认API信息输入是否正确";
    if($rt['statusmsg'] == "Type not found") $nodelist = "请在选择虚拟化类型后保存刷新获取节点列表";
    if($rt['statusmsg'] == "No nodes found") $nodelist = "没有找到符合条件的节点 请检查虚拟化类型是否选择正确";
  }else {
    $nodelist = "请在选择服务器后保存刷新";
  }
  if($rt['status'] == "success") {
    $nodelist = $rt['nodes'];
  }

  $rt = json_decode(svm_curl($svm_api_addr,"id=$svm_api_id&key=$svm_api_key&action=listplans&type=$vtype&rdtype=json",$svm_api_raddr),true);
  if($rt) {
    if($rt['statusmsg'] == "Invalid ipaddress") $planlist = "请确认IP白名单设置是否正确";
    if($rt['statusmsg'] == "API account inactive") $planlist = "API账户已被禁用 请确认配置是否正确";
    if($rt['statusmsg'] == "Invalid id or key") $planlist = "API ID或KEY输入错误 请确认API信息输入是否正确";
    if($rt['statusmsg'] == "Type not found") $planlist = "请在选择虚拟化类型后保存刷新获取套餐列表";
    if($rt['statusmsg'] == "No plans found") $planlist = "没有找到符合条件的套餐 请检查虚拟化类型是否选择正确";
  }else {
    $planlist = "请在选择服务器后保存刷新";
  }
  if($rt['status'] == "success") {
    $planlist = $rt['plans'];
  }

  $rt = json_decode(svm_curl($svm_api_addr,"id=$svm_api_id&key=$svm_api_key&action=listtemplates&type=$vtype&rdtype=json",$svm_api_raddr),true);
  if($rt) {
    if($vtype == "openvz" || $vtype == "xen") $tname="templates";
    if($vtype == "xen hvm") $tname = "templateshvm";
    if($vtype == "kvm") $tname = "templateskvm";
    if($rt['statusmsg'] == "Invalid ipaddress") $templatelist = "请确认IP白名单设置是否正确";
    if($rt['statusmsg'] == "API account inactive") $templatelist = "API账户已被禁用 请确认配置是否正确";
    if($rt['statusmsg'] == "Invalid id or key") $templatelist = "API ID或KEY输入错误 请确认API信息输入是否正确";
    if($rt[$tname] == null) $templatelist = "此虚拟化类型没有可用模板，请在Solusvm后台添加模板";
  }else {
    $templatelist = "请在选择服务器后保存刷新";
  }
  if($rt['status'] == "success") {
    $templatelist = $rt[$tname];
  }
  $Options = array(
    "虚拟化类型" => array(
      "Type" => "dropdown",
      "Options" => "小鸡虚拟化类型(需要安装了对应虚拟化的被控),openvz,xen,xen hvm,kvm"
     ),
    "节点" => array(
      "Type" => "dropdown",
      "Options" => $nodelist
    ),
    "套餐" => array(
      "Type" => "dropdown",
      "Options" => $planlist
    ),
    "默认安装系统" => array(
      "Type" => "dropdown",
      "Options" => $templatelist
    ),
    "IP数量" => array(
      "Type" => "text",
      "Size" => "10",
      "Description" => "给小鸡分配的IP数量"
    ),
    "NAT" => array(
      "Type" => "yesno",
      "Description" => "是否启用NAT(启用后将会在产品配置中显示SSH端口&NAT端口 (SSH端口 61X NAT端口 1X0~1X9)"
    ),
    "NAT公网IP配置" => array(
      "Type" => "text",
      "Size" => "20",
      "Description" => "NAT公网入口IP 即你母鸡的公网IP 如：8.8.8.8 用来展示给用户公网IP"
    ),
    "NAT内网IP配置" => array(
      "Type" => "text",
      "Size" => "20",
      "Description" => "NAT内网IP段 如：10.0.1.0/24则填写10.0.1 *开启NAT时生效 用来计算端口"
    )
  );
return $Options;
}
function Airline_svm_CreateAccount($data) {
  $svm_api_addr = "https://{$data['serverip']}:5656/api/admin/command.php";
  $client_pwd = substr(md5($data['serverip'].$data['clientsdetails']['userid'].get_query_val("用户","密码",array("uid" => $data['clientsdetails']['userid']))),$data['clientsdetails']['userid'][0],10);
  $client_email = $data['clientsdetails']['email'];
  $client_name = $data['clientsdetails']['lastname'];
  $postdata['action'] = "vserver-create";
  $postdata['id'] = $data['serverusername'];
  $postdata['key'] = $data['serverpassword'];
  $postdata['type'] = $data['configoption1'];
  $postdata['node'] = $data['configoption2'];
  $postdata['hostname'] = "i-".substr(md5($data['username'].rand( rand(10,100) , rand(100,1000) )*$data['serviceid']),8,16);
  $postdata['password'] = $data['password'];
  $postdata['username'] = str_replace("@","-",get_query_val("用户","电子邮件",array("uid" => $data['clientsdetails']['userid'])));
  $postdata['plan'] = $data['configoption3'];
  $postdata['template'] = $data['configoption4'];
  $postdata['ips'] = $data['configoption5'];
  foreach($postdata as $n => $v) {
    $svm_postdata .= "$n=$v&";
  }
  $svm_postdata .= "rdtype=json";
  svm_curl($svm_api_addr,"action=client-create&id={$postdata['id']}&key={$postdata['key']}&username={$postdata['username']}&password=$client_pwd&email=$client_email&firstname=$client_name",$data['serveraccesshash']);
  $rt = json_decode(svm_curl($svm_api_addr,$svm_postdata,$data['serveraccesshash']),true);
  if($rt['status'] == "success") {
    update_query("服务",array(
      "用户名" => "root",
      "密码" => encrypt($postdata['password']."<br />控制面板用户名：{$postdata['username']} <br /> 控制面板密码：$client_pwd"),
      "专用IP" => $rt['mainipaddress'],
      "注释" => $rt['vserverid']),array("id" => $data['serviceid']));
    if($data['configoption6'] == "on") {
      $nat_ip = $data['configoption7'];
      $ip_num = str_replace($data['configoption8'].".",null,$rt['mainipaddress']);
      $ssh_port = (61000 + $ip_num);
      if($ip_num < 10) $nat_port_start = "100".$ip_num."0"; $nat_port_end = ($nat_port_start + 9);
      if($ip_num >= 10 && $ip_num < 100) $nat_port_start = "10".$ip_num."0"; $nat_port_end = ($nat_port_end + 9);
      if($ip_num >= 100 && $ip_num < 1000) $nat_port_start = "1".$ip_num."0"; $nat_port_end = ($nat_port_start + 9);
      insert_query("主机自定义配置选项",array("服务id" => $data['serviceid'],"名字" => "NAT入口IP","内容" => $nat_ip));
      insert_query("主机自定义配置选项",array("服务id" => $data['serviceid'],"名字" => "SSH端口","内容" => $ssh_port));
      insert_query("主机自定义配置选项",array("服务id" => $data['serviceid'],"名字" => "起始NAT端口","内容" => $nat_port_start));
      insert_query("主机自定义配置选项",array("服务id" => $data['serviceid'],"名字" => "结束NAT端口","内容" => $nat_port_end));
    }
    return "成功";
  }
  return $rt['statusmsg'];
//  return $svm_api_addr;
}
function Airline_svm_ServerRenewalAccount($data) {
  return "成功";
}
function Airline_svm_UnSuspendAccount($data) {
  $svm_api_addr = "https://{$data['serverip']}:5656/api/admin/command.php";
  $postdata['action'] = "vserver-unsuspend";
  $postdata['vserverid'] = get_query_val("服务","注释",array("id" => $data['serviceid']));
  $postdata['id'] = $data['serverusername'];
  $postdata['key'] = $data['serverpassword'];
  foreach($postdata as $n => $v) {
    $svm_postdata .= "$n=$v&";
  }
  $svm_postdata .= "rdtype=json";
  $rt = json_decode(svm_curl($svm_api_addr,$svm_postdata,$data['serveraccesshash']),true);
  if($rt['status'] == "success") return "成功";
  return $rt['statusmsg'];
}
function Airline_svm_SuspendAccount($data) {
  $svm_api_addr = "https://{$data['serverip']}:5656/api/admin/command.php";
  $postdata['action'] = "vserver-suspend";
  $postdata['vserverid'] = get_query_val("服务","注释",array("id" => $data['serviceid']));
  $postdata['id'] = $data['serverusername'];
  $postdata['key'] = $data['serverpassword'];
  foreach($postdata as $n => $v) {
    $svm_postdata .= "$n=$v&";
  }
  $svm_postdata .= "rdtype=json";
  $rt = json_decode(svm_curl($svm_api_addr,$svm_postdata,$data['serveraccesshash']),true);
  if($rt['status'] == "success") return "成功";
  return $rt['statusmsg'];
}
function Airline_svm_TerminateAccount($data) {
return '成功';
}
function Airline_svm_ClientArea($data) {
  $client_usr = str_replace("@","-",get_query_val("用户","电子邮件",array("uid" => $data['clientsdetails']['userid'])));
  $client_pwd = substr(md5($data['serverip'].$data['clientsdetails']['userid'].get_query_val("用户","密码",array("uid" => $data['clientsdetails']['userid']))),$data['clientsdetails']['userid'][0],10);
  $url = "https://{$data['serverip']}:5656";

  $svm_api_addr = "https://{$data['serverip']}:5656/api/admin/command.php";
  $postdata['action'] = "vserver-infoall";
  $postdata['vserverid'] = get_query_val("服务","注释",array("id" => $data['serviceid']));
  $postdata['id'] = $data['serverusername'];
  $postdata['key'] = $data['serverpassword'];
  foreach($postdata as $n => $v) {
    $svm_postdata .= "$n=$v&";
  }
  $svm_postdata .= "rdtype=json";
  $usage = json_decode(svm_curl($svm_api_addr,$svm_postdata,$data['serveraccesshash']),true);
  $info = json_decode(svm_curl($svm_api_addr,"id={$postdata['id']}&key={$postdata['key']}&action=vserver-info&vserverid={$postdata['vserverid']}&rdtype=json",$data['serveraccesshash']),true);

  $bd = explode(",",$usage['bandwidth']);
  $total = number_format($bd[0] / 1048576 / 1024,3)."GB";
  $use = number_format($bd[1] / 1048576 / 1024,3)."GB";
  $rest = number_format($bd[2] / 1048576 / 1024,3)."GB";
  $bdus = "Total:$total Use:$use Rest:$rest";

  $rt = json_decode(svm_curl($svm_api_addr,"id={$postdata['id']}&key={$postdata['key']}&action=listtemplates&type={$usage['type']}&rdtype=json",$data['serveraccesshash']),true);
  if($rt) {
    if($usage['type'] == "openvz" || $usage['type'] == "xen") $tname="templates";
    if($usage['type'] == "xen hvm") $tname = "templateshvm";
    if($usage['type'] == "kvm") $tname = "templateskvm";
    if($rt['statusmsg'] == "Invalid ipaddress") $templatelist = "请确认IP白名单设置是否正确";
    if($rt['statusmsg'] == "API account inactive") $templatelist = "API账户已被禁用";
    if($rt['statusmsg'] == "Invalid id or key") $templatelist = "API ID或KEY输入错误";
    if($rt[$tname] == null) $templatelist = "此虚拟化类型没有可用模板";
  }
  if($rt['status'] == "success") {
    $templatelist = $rt[$tname];
  }
  
  foreach(explode(",",$templatelist) as $n) {
    $sysoptions .= "<option value=\"$n\">$n</option>";
  }
  $actions = "</div><div><hr />Hostname:{$info['hostname']}<hr />Status:{$usage['state']}<hr />Virtual type:{$usage['type']}<hr />System:{$info['template']}<hr />Server node:{$usage['node']}<hr />Bandwidth usage:$bdus<hr />Reinstall <form action=\"action/reinstall/\"  method=\"post\"><select name=\"template\">$sysoptions</select>&nbsp;<button type=\"submit\">Reinstall</button></form></div><div>";
  return array(
    "<iframe src=\"/swap_mac/swap_lib/servers/Airline_svm/login.php?username=$client_usr&password=$client_pwd&sip=$url\" hidden></iframe>",
    "<a href=\"$url\" target=\"_blank\">登入控制面板</a>",
    "</li></ul>$actions<ul>"
  );
}
function Airline_svm_ChangePassword($data) {
  $sid = $data['serverid'];
  $sip = get_query_val("服务器表","IP",array("id" => $sid));
  $svm_api_addr = "https://$sip:5656/api/admin/command.php";
  $postdata['action'] = "client-updatepassword";
  $postdata['id'] = get_query_val("服务器表",'用户名',array("id" => $sid));
  $postdata['key'] = decrypt(get_query_val("服务器表","密码",array("id" => $sid)));
  $postdata['username'] = str_replace("@","-",get_query_val("用户","电子邮件",array("uid" => $data['clientsdetails']['userid'])));
  $postdata['password'] = substr(md5($data['serverip'].$data['clientsdetails']['userid'].get_query_val("用户","密码",array("uid" => $data['clientsdetails']['userid']))),$data['clientsdetails']['userid'][0],10);
  foreach($postdata as $n => $v) {
    $svm_postdata .= "$n=$v&";
  }
  $svm_postdata .= "rdtype=json";
  $rt = json_decode(svm_curl($svm_api_addr,$svm_postdata,$data['serveraccesshash']),true);
  if($rt['status'] == "success") {
    update_query("服务",array("密码" => encrypt($data['password']."<br />控制面板用户名：{$postdata['username']} <br /> 控制面板密码：{$postdata['password']}")),array("id" => $data['serviceid']));
    return '成功';
  }
  return "公开信息:".$rt['statusmessage'];
}
function Airline_svm_ClientAreaCustomButtonArray() {
  $buttonarray = array(
    "Boot" => "boot",
    "Shutdown" => "shutdown",
    "Reboot" => "reboot",
    "Reinstall" => "reinstall"
  );
  return $buttonarray;
}
function Airline_svm_boot($data) {
  $svm_api_addr = "https://{$data['serverip']}:5656/api/admin/command.php";
  $postdata['action'] = "vserver-boot";
  $postdata['vserverid'] = get_query_val("服务","注释",array("id" => $data['serviceid']));
  $postdata['id'] = $data['serverusername'];
  $postdata['key'] = $data['serverpassword'];
  foreach($postdata as $n => $v) {
    $svm_postdata .= "$n=$v&";
  }
  $svm_postdata .= "rdtype=json";
  $rt = json_decode(svm_curl($svm_api_addr,$svm_postdata,$data['serveraccesshash']),true);
  if($rt['status'] == "success") return "成功";
  return $rt['message'];
}
function Airline_svm_shutdown($data) {
  $svm_api_addr = "https://{$data['serverip']}:5656/api/admin/command.php";
  $postdata['action'] = "vserver-shutdown";
  $postdata['vserverid'] = get_query_val("服务","注释",array("id" => $data['serviceid']));
  $postdata['id'] = $data['serverusername'];
  $postdata['key'] = $data['serverpassword'];
  foreach($postdata as $n => $v) {
    $svm_postdata .= "$n=$v&";
  }
  $svm_postdata .= "rdtype=json";
  $rt = json_decode(svm_curl($svm_api_addr,$svm_postdata,$data['serveraccesshash']),true);
  if($rt['status'] == "success") return "成功";
  return $rt['message'];
}
function Airline_svm_reboot($data) {
  $svm_api_addr = "https://{$data['serverip']}:5656/api/admin/command.php";
  $postdata['action'] = "vserver-reboot";
  $postdata['vserverid'] = get_query_val("服务","注释",array("id" => $data['serviceid']));
  $postdata['id'] = $data['serverusername'];
  $postdata['key'] = $data['serverpassword'];
  foreach($postdata as $n => $v) {
    $svm_postdata .= "$n=$v&";
  }
  $svm_postdata .= "rdtype=json";
  $rt = json_decode(svm_curl($svm_api_addr,$svm_postdata,$data['serveraccesshash']),true);
  if($rt['status'] == "success") return "成功";
  return $rt['message'];
}
function Airline_svm_reinstall($data) {
  if($_POST['template'] == "--none--") return "请选择系统";
  if($_POST['template'] == null) return "请点击选项框旁的'Reinstall'按钮！";
  $svm_api_addr = "https://{$data['serverip']}:5656/api/admin/command.php";
  $postdata['action'] = "vserver-rebuild";
  $postdata['vserverid'] = get_query_val("服务","注释",array("id" => $data['serviceid']));
  $postdata['template'] = $_POST['template'];
  $postdata['id'] = $data['serverusername'];
  $postdata['key'] = $data['serverpassword'];
  foreach($postdata as $n => $v) {
    $svm_postdata .= "$n=$v&";
  }
  $svm_postdata .= "rdtype=json";
  $rt = json_decode(svm_curl($svm_api_addr,$svm_postdata,$data['serveraccesshash']),true);
  if($rt['status'] == "success") return "成功";
  return $rt['message'];
}
function svm_curl($url,$data,$addr) {
    if($addr) $url = str_replace(svm_cut("https://",":5656",$url).":5656",$addr,$url);
    $ch = curl_init();
    $cu[CURLOPT_URL] = $url;
    $cu[CURLOPT_HEADER] = false;
    $cu[CURLOPT_RETURNTRANSFER] = true;
    $cu[CURLOPT_FOLLOWLOCATION] = true;
    $cu[CURLOPT_POST] = true;
    $cu[CURLOPT_POSTFIELDS] = $data;
    $cu[CURLOPT_SSL_VERIFYPEER] = false;
    $cu[CURLOPT_SSL_VERIFYHOST] = false;
    $cu[CURLOPT_TIMEOUT] = "3";
    $cu[CURLOPT_USERAGENT] = "okhttp/3.5.0";
    curl_setopt_array($ch, $cu);
    $content = curl_exec($ch);
    curl_close($ch);
    return $content;
}
function svm_cut($begin,$end,$str) {
    $b = mb_strpos($str,$begin) + mb_strlen($begin);
    $e = mb_strpos($str,$end) - $b;
    return mb_substr($str,$b,$e);
}
function svm_rnd($length) {
 // 密码字符集，可任意添加你需要的字符
 $str = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 
 'i', 'j', 'k', 'l','m', 'n', 'o', 'p', 'q', 'r', 's', 
 't', 'u', 'v', 'w', 'x', 'y','z', 'A', 'B', 'C', 'D', 
 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L','M', 'N', 'O', 
 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y','Z', 
 '0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
 // 在 $str 中随机取 $length 个数组元素键名
 $keys = array_rand($str, $length); 
 $password = '';
 for($i = 0; $i < $length; $i++)
 {
  // 将 $length 个数组元素连接成字符串
  $password .= $str[$keys[$i]];
 }
 return $password;
}
?>