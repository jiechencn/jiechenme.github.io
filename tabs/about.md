---
title: About

# The About page
# v2.0
# https://github.com/cotes2020/jekyll-theme-chirpy
# © 2017-2019 Cotes Chung
# MIT License
---

![](/assets/img/jiechen.jpg)

<div class="post-content">

<p>
<button id="btn-about-lang" type="button" class="btn btn-outline-primary btn-lang pl-1">
<i class="fas fa-language fa-fw mr-1"></i><span>中文</span></button>
</p>

<div id="about-cn" class="">
<p>
英文介绍
</p>
</div>

<div id="about-en" class="unloaded">
<p>
中文文介绍
</p>
</div>

<script type="text/javascript"> $(function() { const LAN_EN = "EN"; const LAN_CN = "中文"; $("#btn-about-lang").click(function() { if ($("#btn-about-lang span").text() == LAN_CN) { $("#about-cn").addClass("unloaded"); $("#about-en").removeClass("unloaded"); $("#btn-about-lang span").text(LAN_EN); } else { $("#about-cn").removeClass("unloaded"); $("#about-en").addClass("unloaded"); $("#btn-about-lang span").text(LAN_CN); } }); }); </script>

</div>

