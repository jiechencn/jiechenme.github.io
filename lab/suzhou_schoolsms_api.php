<?php
include ("mysql_db.php");
$con = new DbConn();

//$str = "[{'smsid': '44401510', 'smstime': '2019/6/10 16:26:44', 'smsteacher': '马静', 'smsbody': '园区教育局、园区处非办提醒您：珍惜自己的血汗钱、保卫父母的养老钱、守住子女的读书钱,不要听信竹篮子也能打一筐水的神话,拒绝高利诱惑、远离非法集资。警惕网络高利借贷风险,遭遇“套路贷”暴力催收等违法犯罪及时向公安机关报案。'}, {'smsid': '42916985', 'smstime': '2019/6/6 8:02:48', 'smsteacher': '王永林', 'smsbody': '陈梓澜家长您好！黏黏粽子香,端午送安康。根据国家法定假日放假安排,方洲小学2019年端午节放假安排如下：6月7日至9日放假,共3天。6月10日周一正常到校。放假期间提醒孩子注意劳逸结合,做好期末复习。——方洲小学教导处'}, {'smsid': '41468981', 'smstime': '2019/6/3 16:44:21', 'smsteacher': '唐啟晴', 'smsbody': '家长您好,本周美术课一节课复习,一节课期末知识素养考核,请孩子们带好一张a4白纸。期末绘画作品还没有交的同学,本周四截止,请您关注。'}, {'smsid': '38468017', 'smstime': '2019/5/26 11:49:02', 'smsteacher': '唐啟晴', 'smsbody': '家长您好,上周美术学科竞赛暨期末绘画考核已经结束,部分孩子的作品还没有交,请提醒周一时明天务必带来.'}, {'smsid': '36272951', 'smstime': '2019/5/20 11:19:51', 'smsteacher': '唐啟晴', 'smsbody': '家长您好,本周是美术学科竞赛的最后一周,周五需要上交孩子们的竞赛作品,此次竞赛,此次竞赛纳入学期期末绘画,优秀作品推荐参加比赛。还请提醒孩子们课上带好相应材料和工具,并且抓紧时间完成。'}, {'smsid': '33498455', 'smstime': '2019/5/12 10:44:10', 'smsteacher': '唐啟晴', 'smsbody': '家长您好,下周美术课将开展学校美术学科竞赛,主题是美好家园。本次竞赛将作为期末绘画考核,历时两周,优秀作品推荐参加比赛。请孩子们带好八开素描纸,勾线笔两支,粗细头彩笔,参考素材,上周课上已提醒,请您再次关注,谢谢！'}, {'smsid': '32848066', 'smstime': '2019/5/10 7:55:26', 'smsteacher': '严芳', 'smsbody': '陈梓澜家长您好！根据上级扫黑除恶要求,请您参与、监督、评判,真正打一场扫黑除恶的人民战争。苏州工业园区扫黑除恶举报电话：扫黑办：0512-65235212；公安分局：110；纪工委监察工委：12388（举报“保护伞”和腐败问题）。【苏州工业园区扫黑除恶专项斗争领导小组办公室】【方洲小学宣】'}, {'smsid': '28858710', 'smstime': '2019/4/29 12:24:59', 'smsteacher': '包敏', 'smsbody': '陈梓澜家长您好,期中调研成绩已经出来,重难点在课堂上已经讲解过了,并让同学们记了自己的成绩、整理了错题,以便家长查看,请知悉哦.另外,班级总体成绩有进步;但是对于个别学生而言,还存在着很大的进步空间,我会针对这些情况,进行反馈.如果您对于学习有所困惑或者建议,也欢迎及时与我沟通,让我们一起更好的陪伴孩子的学习与成长.\x14/握手\x14/握手'}, {'smsid': '26751823', 'smstime': '2019/4/25 11:44:54', 'smsteacher': '王永林', 'smsbody': '陈梓澜家长您好！“五”月鲜花遍方洲,“一”心一意送祝福；幸福在于忘辛“劳”,健康还需常运“动”,美好生活“节”节高；伴随成长最“快”“乐”！“五一”劳动节放假安排如下：2019年5月1日至4日放假调休,共4天。4月28日（星期日）、5月5日（星期日）到校,分别上星期四、星期五的课。方洲小学温馨提示：小长假劳逸结合,合理安排作息哦！——教导处'}, {'smsid': '24288790', 'smstime': '2019/4/18 19:41:25', 'smsteacher': '黄雯', 'smsbody': '陈梓澜家长,您好！明天大课间,学校将请专业人员来校拍摄足球操视频,请您提醒孩子穿好最新的全套秋季校服,里面穿上紫色短袖校服,穿好运动鞋,带好足球。感谢您的配合！'}, {'smsid': '23480693', 'smstime': '2019/4/17 12:18:45', 'smsteacher': '包敏', 'smsbody': '陈梓澜【家校路路通测试短信】家长您好,我会通过路路通以及班级QQ群发送一些学习通知以及反馈学生学习情况,鉴于有的家长反映收不到路路通短信,特发此条短信测试一下,如果您收到短信,请在班级QQ群回复:路路通已收到.我统计一下,将问题反馈给客服.谢谢您的配合.英语包老师'}, {'smsid': '22371701', 'smstime': '2019/4/14 17:47:44', 'smsteacher': '刘健', 'smsbody': '家长您好:天气渐暖,周一升旗仪式上提醒孩子们穿好校服.长衣加长裤.——方洲小学'}, {'smsid': '21193233', 'smstime': '2019/4/10 17:18:57', 'smsteacher': '宋娟娟', 'smsbody': '陈梓澜家长好！4月11日（周四）学校举行综合实践活动,温馨提醒：1.早晨正常时间到校,下午放学时间为一二年级15:20,三四年级15:30,五六年级15:40,放学地点不变；2.学生穿运动校服；3.自备综合实践活动午餐（面包、牛奶、纯净水、水果等）,不要带得太多,带好垃圾袋、桌布、湿纸巾等,不带贵重物品,注意勤俭节约。预祝活动开心！（方洲小学）'}, {'smsid': '19732262', 'smstime': '2019/4/7 20:25:27', 'smsteacher': '唐啟晴', 'smsbody': '家长您好,假期即将结束,下周美术课请提醒孩子带好必带材料,另外请关注：周一就请提前带好一张A4白纸,用于美术课（尺寸必须是要A4否则有影响）,请务必提醒孩子,谢谢您。'}, {'smsid': '19684298', 'smstime': '2019/4/7 16:43:39', 'smsteacher': '刘健', 'smsbody': '家长您好！明天学校举行读书节开幕式,请提醒孩子穿好校服.（长衣+长裤）——方洲小学'}, {'smsid': '4230043', 'smstime': '2019/3/3 16:29:47', 'smsteacher': '唐啟晴', 'smsbody': '家长您好,前两周美术课和孩子们一起学习了不同线条给人的感觉,欣赏了中国古代名画,并且明确了本学期学习任务、评价标准和材料要求。下周美术课请孩子们带好必带材料,感谢您的提醒！'}]";

// suzhou_schoolsms_api.php?op=getmaxid
// suzhou_schoolsms_api.php?op=insertsmses
$op = "";
if (isset($_GET["op"])){
	$op = $_GET["op"];
}


switch($op){
	case "getmaxid":{
		echo getMaxID();
		break;
	}
	case "insertsmses":{
		echo insertSMSes();
		break;
	}
	default:{
		echo "-1";
	}
}


function insertSMSes(){
	
	global $con;
	$amount = 0;
	try{
		$smses = '';
		$smses = $_POST["smses"];
		//$smses = '[{"smsid": "44401510", "smstime": "2019/6/10 16:26:44", "smsteacher": "\u9a6c\u9759", "smsbody": "\u56ed\u533a\u6559\u80b2\u5c40\u3001\u56ed\u533a\u5904\u975e\u529e\u63d0\u9192\u60a8\uff1a\u73cd\u60dc\u81ea\u5df1\u7684\u8840\u6c57\u94b1\u3001\u4fdd\u536b\u7236\u6bcd\u7684\u517b\u8001\u94b1\u3001\u5b88\u4f4f\u5b50\u5973\u7684\u8bfb\u4e66\u94b1,\u4e0d\u8981\u542c\u4fe1\u7af9\u7bee\u5b50\u4e5f\u80fd\u6253\u4e00\u7b50\u6c34\u7684\u795e\u8bdd,\u62d2\u7edd\u9ad8\u5229\u8bf1\u60d1\u3001\u8fdc\u79bb\u975e\u6cd5\u96c6\u8d44\u3002\u8b66\u60d5\u7f51\u7edc\u9ad8\u5229\u501f\u8d37\u98ce\u9669,\u906d\u9047\u201c\u5957\u8def\u8d37\u201d\u66b4\u529b\u50ac\u6536\u7b49\u8fdd\u6cd5\u72af\u7f6a\u53ca\u65f6\u5411\u516c\u5b89\u673a\u5173\u62a5\u6848\u3002"}, {"smsid": "42916985", "smstime": "2019/6/6 8:02:48", "smsteacher": "\u738b\u6c38\u6797", "smsbody": "\u9648\u6893\u6f9c\u5bb6\u957f\u60a8\u597d\uff01\u9ecf\u9ecf\u7cbd\u5b50\u9999,\u7aef\u5348\u9001\u5b89\u5eb7\u3002\u6839\u636e\u56fd\u5bb6\u6cd5\u5b9a\u5047\u65e5\u653e\u5047\u5b89\u6392,\u65b9\u6d32\u5c0f\u5b662019\u5e74\u7aef\u5348\u8282\u653e\u5047\u5b89\u6392\u5982\u4e0b\uff1a6\u67087\u65e5\u81f39\u65e5\u653e\u5047,\u51713\u5929\u30026\u670810\u65e5\u5468\u4e00\u6b63\u5e38\u5230\u6821\u3002\u653e\u5047\u671f\u95f4\u63d0\u9192\u5b69\u5b50\u6ce8\u610f\u52b3\u9038\u7ed3\u5408,\u505a\u597d\u671f\u672b\u590d\u4e60\u3002\u2014\u2014\u65b9\u6d32\u5c0f\u5b66\u6559\u5bfc\u5904"}, {"smsid": "41468981", "smstime": "2019/6/3 16:44:21", "smsteacher": "\u5510\u555f\u6674", "smsbody": "\u5bb6\u957f\u60a8\u597d,\u672c\u5468\u7f8e\u672f\u8bfe\u4e00\u8282\u8bfe\u590d\u4e60,\u4e00\u8282\u8bfe\u671f\u672b\u77e5\u8bc6\u7d20\u517b\u8003\u6838,\u8bf7\u5b69\u5b50\u4eec\u5e26\u597d\u4e00\u5f20a4\u767d\u7eb8\u3002\u671f\u672b\u7ed8\u753b\u4f5c\u54c1\u8fd8\u6ca1\u6709\u4ea4\u7684\u540c\u5b66,\u672c\u5468\u56db\u622a\u6b62,\u8bf7\u60a8\u5173\u6ce8\u3002"}, {"smsid": "38468017", "smstime": "2019/5/26 11:49:02", "smsteacher": "\u5510\u555f\u6674", "smsbody": "\u5bb6\u957f\u60a8\u597d,\u4e0a\u5468\u7f8e\u672f\u5b66\u79d1\u7ade\u8d5b\u66a8\u671f\u672b\u7ed8\u753b\u8003\u6838\u5df2\u7ecf\u7ed3\u675f,\u90e8\u5206\u5b69\u5b50\u7684\u4f5c\u54c1\u8fd8\u6ca1\u6709\u4ea4,\u8bf7\u63d0\u9192\u5468\u4e00\u65f6\u660e\u5929\u52a1\u5fc5\u5e26\u6765."}, {"smsid": "36272951", "smstime": "2019/5/20 11:19:51", "smsteacher": "\u5510\u555f\u6674", "smsbody": "\u5bb6\u957f\u60a8\u597d,\u672c\u5468\u662f\u7f8e\u672f\u5b66\u79d1\u7ade\u8d5b\u7684\u6700\u540e\u4e00\u5468,\u5468\u4e94\u9700\u8981\u4e0a\u4ea4\u5b69\u5b50\u4eec\u7684\u7ade\u8d5b\u4f5c\u54c1,\u6b64\u6b21\u7ade\u8d5b,\u6b64\u6b21\u7ade\u8d5b\u7eb3\u5165\u5b66\u671f\u671f\u672b\u7ed8\u753b,\u4f18\u79c0\u4f5c\u54c1\u63a8\u8350\u53c2\u52a0\u6bd4\u8d5b\u3002\u8fd8\u8bf7\u63d0\u9192\u5b69\u5b50\u4eec\u8bfe\u4e0a\u5e26\u597d\u76f8\u5e94\u6750\u6599\u548c\u5de5\u5177,\u5e76\u4e14\u6293\u7d27\u65f6\u95f4\u5b8c\u6210\u3002"}, {"smsid": "33498455", "smstime": "2019/5/12 10:44:10", "smsteacher": "\u5510\u555f\u6674", "smsbody": "\u5bb6\u957f\u60a8\u597d,\u4e0b\u5468\u7f8e\u672f\u8bfe\u5c06\u5f00\u5c55\u5b66\u6821\u7f8e\u672f\u5b66\u79d1\u7ade\u8d5b,\u4e3b\u9898\u662f\u7f8e\u597d\u5bb6\u56ed\u3002\u672c\u6b21\u7ade\u8d5b\u5c06\u4f5c\u4e3a\u671f\u672b\u7ed8\u753b\u8003\u6838,\u5386\u65f6\u4e24\u5468,\u4f18\u79c0\u4f5c\u54c1\u63a8\u8350\u53c2\u52a0\u6bd4\u8d5b\u3002\u8bf7\u5b69\u5b50\u4eec\u5e26\u597d\u516b\u5f00\u7d20\u63cf\u7eb8,\u52fe\u7ebf\u7b14\u4e24\u652f,\u7c97\u7ec6\u5934\u5f69\u7b14,\u53c2\u8003\u7d20\u6750,\u4e0a\u5468\u8bfe\u4e0a\u5df2\u63d0\u9192,\u8bf7\u60a8\u518d\u6b21\u5173\u6ce8,\u8c22\u8c22\uff01"}, {"smsid": "32848066", "smstime": "2019/5/10 7:55:26", "smsteacher": "\u4e25\u82b3", "smsbody": "\u9648\u6893\u6f9c\u5bb6\u957f\u60a8\u597d\uff01\u6839\u636e\u4e0a\u7ea7\u626b\u9ed1\u9664\u6076\u8981\u6c42,\u8bf7\u60a8\u53c2\u4e0e\u3001\u76d1\u7763\u3001\u8bc4\u5224,\u771f\u6b63\u6253\u4e00\u573a\u626b\u9ed1\u9664\u6076\u7684\u4eba\u6c11\u6218\u4e89\u3002\u82cf\u5dde\u5de5\u4e1a\u56ed\u533a\u626b\u9ed1\u9664\u6076\u4e3e\u62a5\u7535\u8bdd\uff1a\u626b\u9ed1\u529e\uff1a0512-65235212\uff1b\u516c\u5b89\u5206\u5c40\uff1a110\uff1b\u7eaa\u5de5\u59d4\u76d1\u5bdf\u5de5\u59d4\uff1a12388\uff08\u4e3e\u62a5\u201c\u4fdd\u62a4\u4f1e\u201d\u548c\u8150\u8d25\u95ee\u9898\uff09\u3002\u3010\u82cf\u5dde\u5de5\u4e1a\u56ed\u533a\u626b\u9ed1\u9664\u6076\u4e13\u9879\u6597\u4e89\u9886\u5bfc\u5c0f\u7ec4\u529e\u516c\u5ba4\u3011\u3010\u65b9\u6d32\u5c0f\u5b66\u5ba3\u3011"}, {"smsid": "28858710", "smstime": "2019/4/29 12:24:59", "smsteacher": "\u5305\u654f", "smsbody": "\u9648\u6893\u6f9c\u5bb6\u957f\u60a8\u597d,\u671f\u4e2d\u8c03\u7814\u6210\u7ee9\u5df2\u7ecf\u51fa\u6765,\u91cd\u96be\u70b9\u5728\u8bfe\u5802\u4e0a\u5df2\u7ecf\u8bb2\u89e3\u8fc7\u4e86,\u5e76\u8ba9\u540c\u5b66\u4eec\u8bb0\u4e86\u81ea\u5df1\u7684\u6210\u7ee9\u3001\u6574\u7406\u4e86\u9519\u9898,\u4ee5\u4fbf\u5bb6\u957f\u67e5\u770b,\u8bf7\u77e5\u6089\u54e6.\u53e6\u5916,\u73ed\u7ea7\u603b\u4f53\u6210\u7ee9\u6709\u8fdb\u6b65;\u4f46\u662f\u5bf9\u4e8e\u4e2a\u522b\u5b66\u751f\u800c\u8a00,\u8fd8\u5b58\u5728\u7740\u5f88\u5927\u7684\u8fdb\u6b65\u7a7a\u95f4,\u6211\u4f1a\u9488\u5bf9\u8fd9\u4e9b\u60c5\u51b5,\u8fdb\u884c\u53cd\u9988.\u5982\u679c\u60a8\u5bf9\u4e8e\u5b66\u4e60\u6709\u6240\u56f0\u60d1\u6216\u8005\u5efa\u8bae,\u4e5f\u6b22\u8fce\u53ca\u65f6\u4e0e\u6211\u6c9f\u901a,\u8ba9\u6211\u4eec\u4e00\u8d77\u66f4\u597d\u7684\u966a\u4f34\u5b69\u5b50\u7684\u5b66\u4e60\u4e0e\u6210\u957f.\u0014/\u63e1\u624b\u0014/\u63e1\u624b"}, {"smsid": "26751823", "smstime": "2019/4/25 11:44:54", "smsteacher": "\u738b\u6c38\u6797", "smsbody": "\u9648\u6893\u6f9c\u5bb6\u957f\u60a8\u597d\uff01\u201c\u4e94\u201d\u6708\u9c9c\u82b1\u904d\u65b9\u6d32,\u201c\u4e00\u201d\u5fc3\u4e00\u610f\u9001\u795d\u798f\uff1b\u5e78\u798f\u5728\u4e8e\u5fd8\u8f9b\u201c\u52b3\u201d,\u5065\u5eb7\u8fd8\u9700\u5e38\u8fd0\u201c\u52a8\u201d,\u7f8e\u597d\u751f\u6d3b\u201c\u8282\u201d\u8282\u9ad8\uff1b\u4f34\u968f\u6210\u957f\u6700\u201c\u5feb\u201d\u201c\u4e50\u201d\uff01\u201c\u4e94\u4e00\u201d\u52b3\u52a8\u8282\u653e\u5047\u5b89\u6392\u5982\u4e0b\uff1a2019\u5e745\u67081\u65e5\u81f34\u65e5\u653e\u5047\u8c03\u4f11,\u51714\u5929\u30024\u670828\u65e5\uff08\u661f\u671f\u65e5\uff09\u30015\u67085\u65e5\uff08\u661f\u671f\u65e5\uff09\u5230\u6821,\u5206\u522b\u4e0a\u661f\u671f\u56db\u3001\u661f\u671f\u4e94\u7684\u8bfe\u3002\u65b9\u6d32\u5c0f\u5b66\u6e29\u99a8\u63d0\u793a\uff1a\u5c0f\u957f\u5047\u52b3\u9038\u7ed3\u5408,\u5408\u7406\u5b89\u6392\u4f5c\u606f\u54e6\uff01\u2014\u2014\u6559\u5bfc\u5904"}, {"smsid": "24288790", "smstime": "2019/4/18 19:41:25", "smsteacher": "\u9ec4\u96ef", "smsbody": "\u9648\u6893\u6f9c\u5bb6\u957f,\u60a8\u597d\uff01\u660e\u5929\u5927\u8bfe\u95f4,\u5b66\u6821\u5c06\u8bf7\u4e13\u4e1a\u4eba\u5458\u6765\u6821\u62cd\u6444\u8db3\u7403\u64cd\u89c6\u9891,\u8bf7\u60a8\u63d0\u9192\u5b69\u5b50\u7a7f\u597d\u6700\u65b0\u7684\u5168\u5957\u79cb\u5b63\u6821\u670d,\u91cc\u9762\u7a7f\u4e0a\u7d2b\u8272\u77ed\u8896\u6821\u670d,\u7a7f\u597d\u8fd0\u52a8\u978b,\u5e26\u597d\u8db3\u7403\u3002\u611f\u8c22\u60a8\u7684\u914d\u5408\uff01"}, {"smsid": "23480693", "smstime": "2019/4/17 12:18:45", "smsteacher": "\u5305\u654f", "smsbody": "\u9648\u6893\u6f9c\u3010\u5bb6\u6821\u8def\u8def\u901a\u6d4b\u8bd5\u77ed\u4fe1\u3011\u5bb6\u957f\u60a8\u597d,\u6211\u4f1a\u901a\u8fc7\u8def\u8def\u901a\u4ee5\u53ca\u73ed\u7ea7QQ\u7fa4\u53d1\u9001\u4e00\u4e9b\u5b66\u4e60\u901a\u77e5\u4ee5\u53ca\u53cd\u9988\u5b66\u751f\u5b66\u4e60\u60c5\u51b5,\u9274\u4e8e\u6709\u7684\u5bb6\u957f\u53cd\u6620\u6536\u4e0d\u5230\u8def\u8def\u901a\u77ed\u4fe1,\u7279\u53d1\u6b64\u6761\u77ed\u4fe1\u6d4b\u8bd5\u4e00\u4e0b,\u5982\u679c\u60a8\u6536\u5230\u77ed\u4fe1,\u8bf7\u5728\u73ed\u7ea7QQ\u7fa4\u56de\u590d:\u8def\u8def\u901a\u5df2\u6536\u5230.\u6211\u7edf\u8ba1\u4e00\u4e0b,\u5c06\u95ee\u9898\u53cd\u9988\u7ed9\u5ba2\u670d.\u8c22\u8c22\u60a8\u7684\u914d\u5408.\u82f1\u8bed\u5305\u8001\u5e08"}, {"smsid": "22371701", "smstime": "2019/4/14 17:47:44", "smsteacher": "\u5218\u5065", "smsbody": "\u5bb6\u957f\u60a8\u597d:\u5929\u6c14\u6e10\u6696,\u5468\u4e00\u5347\u65d7\u4eea\u5f0f\u4e0a\u63d0\u9192\u5b69\u5b50\u4eec\u7a7f\u597d\u6821\u670d.\u957f\u8863\u52a0\u957f\u88e4.\u2014\u2014\u65b9\u6d32\u5c0f\u5b66"}, {"smsid": "21193233", "smstime": "2019/4/10 17:18:57", "smsteacher": "\u5b8b\u5a1f\u5a1f", "smsbody": "\u9648\u6893\u6f9c\u5bb6\u957f\u597d\uff014\u670811\u65e5\uff08\u5468\u56db\uff09\u5b66\u6821\u4e3e\u884c\u7efc\u5408\u5b9e\u8df5\u6d3b\u52a8,\u6e29\u99a8\u63d0\u9192\uff1a1.\u65e9\u6668\u6b63\u5e38\u65f6\u95f4\u5230\u6821,\u4e0b\u5348\u653e\u5b66\u65f6\u95f4\u4e3a\u4e00\u4e8c\u5e74\u7ea715:20,\u4e09\u56db\u5e74\u7ea715:30,\u4e94\u516d\u5e74\u7ea715:40,\u653e\u5b66\u5730\u70b9\u4e0d\u53d8\uff1b2.\u5b66\u751f\u7a7f\u8fd0\u52a8\u6821\u670d\uff1b3.\u81ea\u5907\u7efc\u5408\u5b9e\u8df5\u6d3b\u52a8\u5348\u9910\uff08\u9762\u5305\u3001\u725b\u5976\u3001\u7eaf\u51c0\u6c34\u3001\u6c34\u679c\u7b49\uff09,\u4e0d\u8981\u5e26\u5f97\u592a\u591a,\u5e26\u597d\u5783\u573e\u888b\u3001\u684c\u5e03\u3001\u6e7f\u7eb8\u5dfe\u7b49,\u4e0d\u5e26\u8d35\u91cd\u7269\u54c1,\u6ce8\u610f\u52e4\u4fed\u8282\u7ea6\u3002\u9884\u795d\u6d3b\u52a8\u5f00\u5fc3\uff01\uff08\u65b9\u6d32\u5c0f\u5b66\uff09"}, {"smsid": "19732262", "smstime": "2019/4/7 20:25:27", "smsteacher": "\u5510\u555f\u6674", "smsbody": "\u5bb6\u957f\u60a8\u597d,\u5047\u671f\u5373\u5c06\u7ed3\u675f,\u4e0b\u5468\u7f8e\u672f\u8bfe\u8bf7\u63d0\u9192\u5b69\u5b50\u5e26\u597d\u5fc5\u5e26\u6750\u6599,\u53e6\u5916\u8bf7\u5173\u6ce8\uff1a\u5468\u4e00\u5c31\u8bf7\u63d0\u524d\u5e26\u597d\u4e00\u5f20A4\u767d\u7eb8,\u7528\u4e8e\u7f8e\u672f\u8bfe\uff08\u5c3a\u5bf8\u5fc5\u987b\u662f\u8981A4\u5426\u5219\u6709\u5f71\u54cd\uff09,\u8bf7\u52a1\u5fc5\u63d0\u9192\u5b69\u5b50,\u8c22\u8c22\u60a8\u3002"}, {"smsid": "19684298", "smstime": "2019/4/7 16:43:39", "smsteacher": "\u5218\u5065", "smsbody": "\u5bb6\u957f\u60a8\u597d\uff01\u660e\u5929\u5b66\u6821\u4e3e\u884c\u8bfb\u4e66\u8282\u5f00\u5e55\u5f0f,\u8bf7\u63d0\u9192\u5b69\u5b50\u7a7f\u597d\u6821\u670d.\uff08\u957f\u8863+\u957f\u88e4\uff09\u2014\u2014\u65b9\u6d32\u5c0f\u5b66"}, {"smsid": "4230043", "smstime": "2019/3/3 16:29:47", "smsteacher": "\u5510\u555f\u6674", "smsbody": "\u5bb6\u957f\u60a8\u597d,\u524d\u4e24\u5468\u7f8e\u672f\u8bfe\u548c\u5b69\u5b50\u4eec\u4e00\u8d77\u5b66\u4e60\u4e86\u4e0d\u540c\u7ebf\u6761\u7ed9\u4eba\u7684\u611f\u89c9,\u6b23\u8d4f\u4e86\u4e2d\u56fd\u53e4\u4ee3\u540d\u753b,\u5e76\u4e14\u660e\u786e\u4e86\u672c\u5b66\u671f\u5b66\u4e60\u4efb\u52a1\u3001\u8bc4\u4ef7\u6807\u51c6\u548c\u6750\u6599\u8981\u6c42\u3002\u4e0b\u5468\u7f8e\u672f\u8bfe\u8bf7\u5b69\u5b50\u4eec\u5e26\u597d\u5fc5\u5e26\u6750\u6599,\u611f\u8c22\u60a8\u7684\u63d0\u9192\uff01"}]';
		$smsesarr = json_decode($smses, true);
		
		$sql = '';
		for($i=0;$i<count($smsesarr);$i++){
			 $sql = 'insert into suzhou_schoolsms(smsid, smstime, smsteacher, smsbody) values (' . $smsesarr[$i]["smsid"] . ', "' . $smsesarr[$i]["smstime"] . '","' . $smsesarr[$i]["smsteacher"] . '","' . $smsesarr[$i]["smsbody"] . '")';
			 $con->Execute($sql);
			 $amount++;
		}	
		return $amount;
	}catch(Exception $e){
		$con->Quit();
		return $amount;
	}
	
}


function getMaxID(){
	global $con;
	$sql = "select max(smsid) from suzhou_schoolsms";
	$maxid = $con->getRs($sql); 
	return $maxid[0];
}

$con->Quit();
?>