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
<p>
<button id="btn-about-lang" type="button" class="btn btn-outline-primary btn-lang pl-1">
<i class="fas fa-language fa-fw mr-1"></i><span>中文</span></button>
</p>

<div id="about-cn" class="">
<p>
Hey, I am Jie Chen, an aged software engineer in Microsoft.
</p>
</div>

<div id="about-en" class="unloaded">
<p>
欢迎！
<br/>
我刚刚离开Oracle，现在Microsoft，年老色衰，继续搬砖。 
</p>
</div>

<script type="text/javascript"> $(function() { const LAN_EN = "EN"; const LAN_CN = "中文"; $("#btn-about-lang").click(function() { if ($("#btn-about-lang span").text() == LAN_CN) { $("#about-cn").addClass("unloaded"); $("#about-en").removeClass("unloaded"); $("#btn-about-lang span").text(LAN_EN); } else { $("#about-cn").removeClass("unloaded"); $("#about-en").addClass("unloaded"); $("#btn-about-lang span").text(LAN_CN); } }); }); </script>

</div>

