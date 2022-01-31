---
title: About

# The About page
# v2.0
# https://github.com/cotes2020/jekyll-theme-chirpy
# © 2017-2019 Cotes Chung
# MIT License
---

<div class="post-content">
<p align="left">
<img src="/assets/img/jiechen.jpg" width="120" height="120" style="border-radius: 50%;">
</p>

<div>
<p>
欢迎！
<br/>
我刚刚离开Oracle，现在Microsoft，年老色衰，继续搬砖。 <span id="idTimeToRetire"></span>
</p>


<script>
  var countDownDate = new Date("May 07, 2044").getTime();
  var now = new Date().getTime();
  var distance = countDownDate - now;
  var days = Math.floor(distance / (1000 * 60 * 60 * 24));
  var info = "";
  if (distance < 0) {
     info = "已经退休啦。";
  }else{
    info = "还有" + days + "天退休。";
  }
  document.getElementById("idTimeToRetire").innerHTML = info;
</script>
</div>


</div>

