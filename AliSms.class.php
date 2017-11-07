<?php
/**
 *
 * @authors  Ysg (y.shi.guo@gmail.com)
 * @website  http://ysg.bonza.cn
 * @date     2017-10-19 10:15:51
 */

namespace sms;
/**
* 短信发送类
*/
class AliSms implements Sms
{
    const  TARGET="http://dysmsapi.aliyuncs.com/?";//接口地址
    private $code           ='';
    private $format         = 'json';
    // private $format         = 'XML';
    private $common_params  = [];//公共参数,系统参数
    private $private_params = [];//请求参数,业务参数
    private $phone=0;//用户手机号
    private $template_id ='SMS_104410009';//短信模板id
    private $app_key = '';
    private $app_secret = '';
    // private $app_key = 'testId';
    // private $app_secret = 'testSecret';
    private $action = 'SendSms';
    // private $action = 'DescribeRegions';
    private $signature_method = 'HMAC-SHA1';
    private $request_method = 'GET';
    private $signname = '播芽';
    private $template_param = "{\"code\":\"110335\"}";
    private $signature_nonce = '';

    public function __construct($length = 6 , $numeric = 1)
    {
        $this->code=$this->random($length,$numeric);
        $this->signature_nonce=$this->random(16,0);
        $this->common_params = $this->staticCommonParams();
        $this->private_params = $this->setPrivateParams();
        // echo $numeric.'---';
    }
    /**
     * 构建请求系统参数
     * @return [type] [description]
     */
    private function staticCommonParams(){
        return [
            'AccessKeyId'=>$this->app_key,
            'Timestamp'=>$this->getTimestamp(),
            'Format'=>$this->format,
            'SignatureMethod'=>$this->signature_method,
            'SignatureVersion'=>'1.0',
            'SignatureNonce'=>$this->signature_nonce,
        ];
    }
    private function setCommonParams(){
           return ['Signature'=>$this->getSign()] + $this->staticCommonParams();
    }
    /**
     * 构建请求业务参数
     */
    private function setPrivateParams(){
        return [
            'Action'=>$this->action,
            'Version'=>'2017-05-25',
            'RegionId'=>'cn-hangzhou',
            'SignName'=>$this->signname,
            'PhoneNumbers'=>$this->phone,
            'TemplateCode'=>$this->template_id,
            'TemplateParam'=>$this->setTemplateParam(),
            // 'OutId' =>'123',
        ];
    }

    public function setTemplateParam(){
        $this->template_param = "{\"code\":\"$this->code\"}";
        return $this->template_param;
    }
    public function setSignName($signname){
        $this->signname = $signname;
    }
    //签名算法
    public function getSign(){
        $params = $this->staticCommonParams() + $this->setPrivateParams();
        $signstring = '';
        ksort($params);
        foreach ($params as $k => &$v) {
            if (!empty($v)) {
                urlencode($k);
                urlencode($v);
            }else{
                unset($params[$k]);
            }
        }
        $stringqurey = http_build_query($params);
        $stringqurey = $this->aliSmsEncode($stringqurey);
        $str_sms = $this->request_method.'&' .urlencode('/').'&'.$this->aliSmsEncode(urlencode($stringqurey));
        // echo $str_sms;die;
        // $str_sms = str_replace('%26','&',$str_sms);
        $sign = base64_encode(hash_hmac('sha1', $str_sms, $this->app_secret.'&', true));
        // print_r($sign);die;
        // print_r($params);die;
        return $sign;
    }
    /**
     * 阿里云通信，短信接口，编码规则
     * @param  [type] $str [description]
     * @return [type]      [description]
     */
    private function aliSmsEncode($str){
        $str=str_replace('+','%20',$str);
        $str=str_replace('*','%2A',$str);
        $str=str_replace('%7E','~',$str);
        return $str;
    }
    /**
     * 设置短信模板id
     * @param string $template_id [SMS_104410009]
     */
    public function setTemplateId($template_id=''){
        $this->template_id = $template_id;
        return $this->template_id;
    }
    /**
     *接收验证码的手机号码
     * @param [type] $phone [手机号码]
     */
    public function setPhone($phone){
        if (strlen($phone) == 11) {
            $this->phone = $phone;
        }else{
            exit('手机号不正确');
        }

    }
    /**
     * 生成短信验证码规则参数
     */
    private function setSmsParams(){
        return json_encode([
            'code'=>$this->code,
        ]);
    }
    public function setFormat($format='json'){
        $this->format = $format;
    }
    private function getTimestamp(){
        // return '2017-07-12T02:42:19Z';
        return date("Y-m-d\TH:i:s\Z");
    }
    //生产验证码
    private function random($length, $numeric) {
        // PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
        //$numberic=0表示发送字母加数字的验证码否则发送纯数字验证码，$length表示验证码的长度
        if($numeric) {
            $hash = sprintf('%0'.$length.'d', mt_rand(0, pow(10, $length) - 1));
        } else {
            $hash = '';
            $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz';
            $max = strlen($chars) - 1;
            for($i = 0; $i < $length; $i++) {
                $hash .= $chars[mt_rand(0, $max)];
            }
        }
        return $hash;
    }
    //获取验证码
    public function getCode(){
        return $this->code;
    }
     /**
      * 发送验证码
      * @return [type]              []
      */
    public function sendSms(){
        // $this->getSign();
        $params = array_merge($this->staticCommonParams(),$this->setPrivateParams());
        ksort($params);
        $params = array_merge(['Signature'=>$this->getSign()],$params);
        foreach ($params as $k => &$v) {
            rawurlencode($k);
            rawurlencode($v);
        }
        $http_query = http_build_query($params);
        $url = self::TARGET.$http_query;
        // echo $url;die;
        // print_r($params);die;
        // $result = $this->http_post(self::TARGET,$params);
        $result = $this->http_get($url);
        // echo $result;die;
        $res = json_decode($result,true);
        print_r($res);
        // $gets = $this->http_post(self::TARGET,$post_data);
    }

    //短信发送方法
    private function Post($data) {
        $url_info = parse_url(self::TARGET);
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader .= "Host:" . $url_info['host'] . "\r\n";
        $httpheader .= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader .= "Content-Length:" . strlen($data) . "\r\n";
        $httpheader .= "Connection:close\r\n\r\n";
        //$httpheader .= "Connection:Keep-Alive\r\n\r\n";
        $httpheader .= $data;

        $fd = fsockopen($url_info['host'], 80);
        fwrite($fd, $httpheader);
        $gets = "";
        while(!feof($fd)) {
            $gets .= fread($fd, 128);
        }
        fclose($fd);
        return $gets;
    }

        /**
     * GET 请求
     * @param string $url
     */
    private function http_get($url){
        $oCurl = curl_init();
        if(stripos($url,"https://")!==FALSE){
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        // var_dump($aStatus);
        // echo $sContent;die;
        if(intval($aStatus["http_code"])==200){
            return $sContent;
        }else{
            return false;
        }
    }

    /**
     * POST 请求
     * @param string $url
     * @param array $param
     * @param boolean $post_file 是否文件上传
     * @return string content
     */
    private function http_post($url,$param,$post_file=false){
        $oCurl = curl_init();
        if(stripos($url,"https://")!==FALSE){
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
            if (PHP_VERSION_ID >= 50500 && class_exists('\CURLFile')) {
                    $is_curlFile = true;
            } else {
                $is_curlFile = false;
                    if (defined('CURLOPT_SAFE_UPLOAD')) {
                        curl_setopt($oCurl, CURLOPT_SAFE_UPLOAD, false);
                    }
            }
        if (is_string($param)) {
                    $strPOST = $param;
            }elseif($post_file) {
                    if($is_curlFile) {
                        foreach ($param as $key => $val) {
                                if (substr($val, 0, 1) == '@') {
                                    $param[$key] = new \CURLFile(realpath(substr($val,1)));
                                }
                        }
                    }
            $strPOST = $param;
        } else {
            $aPOST = array();
            foreach($param as $key=>$val){
                $aPOST[] = $key."=".urlencode($val);
            }
            $strPOST =  join("&", $aPOST);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($oCurl, CURLOPT_POST,true);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS,$strPOST);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if(intval($aStatus["http_code"])==200){
            return $sContent;
        }else{
            return false;
        }
    }
}
