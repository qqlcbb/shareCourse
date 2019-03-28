var xmlhttp; 
function createRequest(){
	xmlhttp = new XMLHttpRequest(); 
	xmlhttp.onreadystatechange = callbacksuccess    //回调函数
	xmlhttp.open('post', "https://www.ttqiwu.com/user/private/del.html", true);     //发送ajax
	xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
	xmlhttp.send({co_id: 111});  //get时data为null
}
function callbacksuccess()
{
  if (xmlhttp.readyState==4 && xmlhttp.status==200)
     {
        // 成功后执行的代码
        console.log(xmlhttp.responseText);  //服务器返回数据为xmlhttp.responseText
     }
}
createRequest();