<?php
/**
 *  Author hlx
 *  Email  5644139**@qq.com
 *  time   2019-01-31
 */
namespace plugins\statistics\controller;
header('Content-type:text/html; Charset=utf-8');
date_default_timezone_set('PRC');
class Haikang
{
    public $pre_url = "https://111.3.64.34:446";
    protected $app_key = "24387968";
    protected $app_secret = "DDZd1gZIBoEvwUKx0vVx";

    public $time ;//时间戳
    public $content_type="application/json";//类型
    public $accept="*/*" ;//accept

    public $face_list_url = "/artemis/api/frs/v1/face/single/addition";//添加人脸照片
    public $upload_face_list_url = "/artemis/api/frs/v1/face/single/update";//修改人脸人脸照片
    public $group_list_url = "/artemis/api/frs/v1/face/group/single/addition";//添加分组
    public $group_search_url = "/artemis/api/frs/v1/face/group";//搜索分组





    public $api_search_url = "/artemis/api/resource/v1/cameras";//搜索分组
    public $video_search_url = "/artemis/api/video/v1/cameras/previewURLs";//搜索分组

    public function __construct($app_key='', $app_secret='')
    {
        if($app_key!='') $this->app_key = $app_key;
        if($app_secret!='') $this->app_secret = $app_secret;
        $this->charset = 'utf-8';
        list($msec, $sec) = explode(' ', microtime());
        $this->time = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    }
    function add_group($groupName)
    {
        $postData["name"] = $groupName;
        $postData["description"] = $groupName;
        $result = $this->doCurl($postData, $this->group_list_url);
        return json_decode($result, true);
    }
    function add_face_img($faceGroupIndexCode, $faceInfo, $img_url)
    {
        $postData["faceGroupIndexCode"] = $faceGroupIndexCode;
        $postData["faceInfo"] = $faceInfo;
        $postData["facePic"] = ["faceUrl"=>$img_url];
        $result = $this->doCurl($postData, $this->face_list_url);
        return json_decode($result, true);
    }
    function update_face_img($faceIndexCode, $faceInfo, $img_url)
    {
        $postData["indexCode"] = $faceIndexCode;
        $postData["faceInfo"] = $faceInfo;
        $postData["facePic"] = ["faceUrl"=>$img_url];
        $result = $this->doCurl($postData, $this->upload_face_list_url);
        return json_decode($result, true);
    }

    function search_group($groupName)
    {
        $postData["name"] = $groupName;
//        $postData["description"] = $groupName;
        $result = $this->doCurl($postData, $this->group_search_url);
        return json_decode($result, true);
    }
    /**
     * 获取人员列表
     */
    function get_person_list($response){
        //请求参数
//        $postData['pageNo'] = isset($response['pageNo']) ? intval($response['pageNo']):"1";
//        $postData['pageSize'] = isset($response['pageSize']) ? intval($response['pageSize']):"1000";
//        $postData['indexCodes'] = ["1aeaa4ff-8d24-4143-a7c4-506e5b0ca559"];
        $postData['name'] = "张三";
//        $postData['faceGroupIndexCode'] = "7cc0adb2-a3c3-48fd-b432-718103e85c28";
//        $postData['name'] = "仓库值守人员";
//        $postData["description"] = "仓库值守人员是指守着仓库的人";
//        $postData["faceInfo"] = ["name"=> "张三", "sex"=> "1"];
//        $postData["facePic"] = ["faceUrl"=> "http://js2.300c.cn/lhyd/public/uploadFile/20200220/0b3d616e63d3aea9035dda91e3ad9692.jpg"];
        $result = $this->doCurl($postData, $this->face_list_url);
        return $result;
    }


    /**
     * 以appSecret为密钥，使用HmacSHA256算法对签名字符串生成消息摘要，对消息摘要使用BASE64算法生成签名（签名过程中的编码方式全为UTF-8）
     */
    function get_sign($postData,$url){
        $sign_str = $this->get_sign_str($postData,$url); //签名字符串
        $priKey=$this->app_secret;
        $sign = hash_hmac('sha256', $sign_str, $priKey,true); //生成消息摘要
        $result = base64_encode($sign);
        return $result;
    }

    function get_sign_str($postData,$url){
        // $next = "\n";
        $next = "\n";
        $str = "POST".$next.$this->accept.$next.$this->content_type.$next;
        $str .= "x-ca-key:".$this->app_key.$next;
        $str .= "x-ca-timestamp:".$this->time.$next;
        $str .= $url;
        return $str;
    }

    public function getSignContent($params) {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;$len = count($params);
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                // 转换成目标字符集
                $v = $this->characet($v, $this->charset);
                if ($i == 0) {
                    $stringToBeSigned .= "?$k" . "=" . "$v";
                }else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }
        unset ($k, $v);
        return $stringToBeSigned;
    }

    function get_message($postData){
        $str = str_replace(array('{','}','"'),'',json_encode($postData));
        return base64_encode(md5($str));
    }
    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     **/
    protected function checkEmpty($value) {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;
        return false;
    }

    /**
     * 转换字符集编码
     * @param $data
     * @param $targetCharset
     * @return string
     */
    function characet($data, $targetCharset) {
        if (!empty($data)) {
            $fileType = $this->charset;
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
            }
        }
        return $data;
    }
    function doCurl($postData, $url)
    {
        $sign = $this->get_sign($postData,$url);
        $options = array(
            CURLOPT_HTTPHEADER => array(
                "Accept:".$this->accept,
                "Content-Type:".$this->content_type,
                "x-Ca-Key:".$this->app_key,
                "X-Ca-Signature:".$sign,
                "X-Ca-Timestamp:".$this->time,
                "X-Ca-Signature-Headers:"."x-ca-key,x-ca-timestamp",
            )
        );
        $result = $this->curlPost($this->pre_url.$url,json_encode($postData),$options);
        return $result;
    }
    public function curlPost($url = '', $postData = '', $options = array())
    {
        if (is_array($postData)) {
            $postData = http_build_query($postData);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}