# web 安全
--
## XSS 跨站脚本攻击
> 黑客通过HTML注入改动网页，插入了恶意脚本，从而在用户浏览网页时，控制用户浏览器的一种攻击。

看一个例子：假设把用户输入的参数直接输出到网页上。  
test1.php

```php
$input = $_GET["param"];
echo "<div>".$input."</div>";
```

>现在的主流浏览器已经默认自带并开启了对于XSS（Cross Site Scripting）攻击的防护，Chrome使用的是XSS Auditor，其目的是识别URL请求参数中的存在的恶意Javascript脚本，并阻止这些脚本在服务器响应中执行。XSS Auditor采用黑名单方式来识别请求参数中的危险字符和标签，如果URL请求的参数与响应的内容不匹配，XSS Auditor则不会触发。  
>ps：暂时没找到染过xss Auditor进行Xss攻击。

### 反射型XSS
> 反射型XSS只是简单地把用户输入的数据“反射”给浏览器，也就是说，黑客往往诱使用户“点击“一个恶意链接，才能攻击成功。反射型XSS也叫做”非持久型XSS“。

### 存储型XSS
> 存储型XSS会把用户输入的数据“存储”在服务端，这种XSS具有很强的稳定性。

黑客写一篇恶意的javascript代码的博客文章，文章发表后，所有访问该博客文章的用户，都会在他们的浏览器中执行这段恶意的javascript代码。

### DOM Based Xss
> 通过修改页面的DOM节点形成的XSS。
比如有这样一个例子test2.html

--
### XSS攻击进阶
> XSS攻击成功后，攻击者能够对用户当前浏览的页面植入恶意的脚本，通过恶意脚本控制用户浏览器，这些完成具体功能的恶意脚本，被称为XSS Payload。实际上就是通过javascript脚本。

一个常见的XSS Payload，就是通过读取浏览器的cookie对象，从而发起“Cookie 劫持”.  
cookie 一般加密保存了当前用户的登录凭证，cookie如果丢失，往往意味着用户的登录凭证丢失。换句话说，攻击者可以不通过密码，直接登录进用户的账户。
比如:  

```
http://127.0.0.1:8888/test1.php?param=<script%20src=http://127.0.0.1:8888/test3.js%20%3E%3C/script> 
```
真正的XSS Payload 写在这个远程的脚本中，避免url的参数里加入大量的javascript代码。  

> 在test3.js中通过代码窃取cookie。把document.cookie对象作为参数发送到远程服务器。
> 事实上 http:127.0.0.1:9999?log并不一定存在。远程服务器中的日志留下记录。

```
GET /log?cookie%3Dxxxxxxxx HTTP/1.1", upstream: "fastcgi://127.0.0.1:9000", host: "127.0.0.1:9999"
```

这样就完成了一个简单的窃取cookie的XSS Payload。

> 请问有人知道窃取cookie的作用是什么吗？cookie和session的区别？


### 强大的XSS payload
> cookie劫持并非所有时候都有效。有的网站会在set-cookie时给关键的cookie与客户端IP绑定，有些会植入HttpOnly标识。

#### 构造GET与POST请求 
比如在TT起舞上有一节私教课程，想通过XSS删除它，该如何做呢？
正常删除私教课程的链接.通过post方式，发送课程ID co_id
```
http://www.ttqiwu.com/user/private/del.html
```

要模拟这个过程 怎么做？构造一个ajax发送post请求（test4.js）。想办法让用户引入这个js。



### XSS 构造技巧
> 介绍常见的XSS攻击技巧。

#### 染过长度限制
假设下面存在一个XSS漏洞

```
<input type="text" value="$val"/>
```

服务端如果没有对变量"$var"做了严格的长度限制，那么可以这样进行攻击：

```
$var为： "><script>alert(/xss/)</script>
```
那么达到输出效果是：

```
<input type="text" value=""><script>alert(/xss/)</script>"/>
```

假设服务端输出长度有限制为20个字节，则这段XSS会被切割为：

```
$var 输出为： "><script>alert(/xss
```
一个完整的函数无法写完，XSS攻击可能无法成功。攻击者可以利用时间来缩短所需要的字节数。

```
$var输出为: "onclick=alert(1)//
```
这是实际输出为：

```
<input type="text" value="" onclick=alert(1)//"/>
```
利用事件能够缩短的字节数是有限的。最好的办法是把XSS Payload写到别处，再通过简短的代码家在这段XSS Payload。

### XSS的防御
> XSS的防御是复杂的，流行的浏览器都内置了一些对抗XSS的攻击，比如前面所说的chrome的XSS Auditor。

#### HttpOnly
> 前面我们尝试使用javascript窃取cookie，如果cookie植入了HttpOnly，那浏览器禁止页面的javascript访问带有HttpOnly的cookie。

关于HttpOnly，我们看一下例子test5.php

```php
<?php
header("Set-Cookie: cookie1=test1;");
header("Set-Cookie: cookie1=test2;httponly", false);

?>

<script>
	alert(document.cookie);
</script>
```

这段代码中，cookie1没有httponly，而cookie2被标记了。两个cookie均被写入浏览器。只有cookie1被javascript读取到。

#### 输入检查
> 一般是检查用户输入的数据是否包含一些特殊字符，如<,>等。如  果发现存在特殊的字符，则将这些字符过滤或者编码。比较智能的输入检查，还会匹配XSS的特征，比如查找用户是否包含"javascript"等敏感字符。
> 这种检查方式，称为XSS Filter。公司俊钟也写过一篇wiki

```
http://wiki.ruizhutech.com/doku.php?id=经验:关于_php_html_过滤器_htmlpurifier_的使用&s%5B%5D=xss
```

#### 输出检查
> 一般来说，除了富文本输出外，在变量输出到HTML页面，可以使用编码或者转义的方式来防御XSS攻击。针对HTML代码的编码方式是HTMLEncode，它是一种函数的实现，它的作用是将字符转换成HTMLEntities，对应的标准ISO-8859-1.  
> php中，有htmlentities和htmlspecialchars可以满足安全要求。htmlentities会格式化中文字符使得中文输入是乱码。

#### 处理富文本
> 网站需要允许用户提交一些自定义的html代码。  
> 在过滤富文本，“事件”应该被禁止，因为展示需要不应该包含“事件”这种动态效果，而一些危险标签如script也应该禁止。
> 在标签的选择是，应该使用白名单，避免使用黑名单。比如，只允许a,img,div等比较安全的标签。

## 跨站点请求伪造（CSRF）

> 假设有这样一个例子，登录某博客后，只要get方法请求这样一个url，就能把编号123的博客文章删除。

```
http://www.a.html/user/blog/del/123
```

这个url中存在CSRF漏洞，我们尝试使用CSRF漏洞删除123这篇文章。我们在自己的域构造一个页面。  
http:// www.b.com/csrf.html 其内容为

```
 <img src="http://www.a.html/user/blog/del/123"/>
```

使用了一个img标签，其地址指向了删除博客文章的链接。  
只要我们诱使目标用户，也就是博主自己访问我们的页面，就能把文章删除。图片标签向博客服务器发送了一个GET请求。  
> 整个攻击过程，攻击者仅仅诱使用户访问一个页面，就以该用户的身份在第三方站点执行了一次操作。这个删除文章的请求，是攻击者所伪造的，这种攻击叫做“跨站点请求伪造"

### CSRF 进阶
> 有人知道上面那个请求为什么能被博客的服务器验证通过？ 

之所以能被服务器验证通过，是因为用户的浏览器成功发送了cookie的缘故，在浏览网站过程中，若是一个网站设置了session cookie，那么在浏览器进程的生命周期内，即使新打开了一个tab页，session cookie也都是有效的，session cookie 保存在浏览器进程的内容空间里。
 

#### 浏览器的Cookie策略
> 浏览器所持有的cookie分两种，一种是”Session Cookie“，又称为“临时cookie”;另一种是“Third-party Cookie”，也称为“本地Cookie”。  
> 两者的区别是：Third-party Cookie是服务器在set-cookie时制定expire事件，只要到了expire时间，cookie才会失效，所以这种cookie保存在本地。而session cookie则没有制定expire时间，所以浏览器关闭后，session cookie就失效了。
  
如果浏览器从一个域，加载另一个域的资源，由于安全原因，某些浏览器会阻止Third-party cookie的发送。  

#### GET？POST？
> 在CSRF流行之初，有一种错误的观点，认为CSRF只能由GET请求发起，因此很多开发者都认为只要把重复的操作改为只允许POST请求，聚能防止CSRF攻击。  

> 这种错误的观点形成的原因在于：大多数CSRF攻击发起时，使用的HTML标签都是img，iframe，script的那个带有src属性的标签，这类标签智能发起一个GET请求，不能发起POST请求。有些网站应用，一些重要的操作并未严格地区分GET和POST请求。比如在php中，使用`$_REQUEST`，而非`$_POST`获取变量，则存在这个问题。

对于一个表单来说，用户往往也就可以用GET方式提交参数：

```
<form action="/test" method="get">
<input type=text name="test1" value="" />
<input type=text name="test2" value="" />
<input type=submit name="submit" value="submit" />
</form>
```
攻击者可以构造这样一个GET请求进行攻击：

```
http://test.com/test?test1=123&test2=456
```
如果服务器端未对请求方式进行限制，则这个请求会通过。  

如果服务器端意见区分好GET和POST，如何用POST进行攻击？最简单的方法是在一个页面中构造好一个form表单，用javascript自动提交这个表单。比如攻击者在www.b.com/test.html中编写如下代码：

```
<form action="http://test.com/test" method="get" id="f">
<input type=text name="test1" value="" />
<input type=text name="test2" value="" />
<input type=submit name="submit" value="submit" />
</form>
<script>
var f = document.getElementById('f');
f.input[0].value = "123";
f.input[1].value = "456";
f.submit;
</script>
```

攻击者把这个页面隐藏在一个不可见的iframe窗口中，那么整个自动提交的表单的过程，对于用户来说都是不可见的。

### CSRF的防御

#### 验证码
> CSRF攻击的过程，往往都是在用户不知情的情况下构造了网络请求，而验证码，则强制用户与应用进行交互，才能完成最终请求。但是出于用户考虑，网站不能给所有操作加上验证码。这个这时防御CSRF一种辅助工具，不能作为最主要的解决方案。

#### Token
csrf为什么能够攻击成功，本质原因是重要操作的所有参数都是可以被攻击者所猜测到的。攻击者之遥预测出URL所有参数与参数值，才能成功地构造一个伪造的请求，反之，攻击者则无法成功。  
出于这个原因，有这样一个解决方法，保持原有的参数不变，新增一个参数Token，这个Token是随机的。不可预测的。
比如：

```
http://www.a.html/user/blog/del/123/token/XXXXXXX
```
token只要足够所及，必须使用足够安全的随机数生成算法。token为用户与服务器所共同持有，不能被第三方知晓。在实际应用中，token可以放在用户的session中，或者浏览器的cookie中。  
由于token的存在，攻击者无法再构造一个完整的URL实施CSRF攻击。

> token需要同是存放在表单和session中，在提交请求时，服务器只要验证表单中的token与用户session中的token是否一致，如果一致，则认为是合法请求，如果不合法，则请求失败。

