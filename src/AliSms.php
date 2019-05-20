<?php
/**
 *
 * @authors  Ysg (y.shi.guo@gmail.com)
 * @website  http://ysg.bonza.cn
 * @date     2017-10-19 10:15:51
 */

namespace bonza\sms;
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
    private $signName = '播芽';
    private $template_param = "{\"code\":\"110335\"}";
    private $signature_nonce = '';
    private $OutId = '';

    public function __construct($length = 6 , $numeric = 1)
    {
        $this->code=$this->random($length,$numeric);
        $this->signature_nonce=$this->random(16,0);
        $this->common_params = $this->commonParams();
        $this->private_params = $this->setPrivateParams();
    }

    /**
     * @return string
     */
    public function getOutId(): string
    {
        return $this->OutId;
    }

    /**
     * @param string $OutId
     */
    public function setOutId(string $OutId)
    {
        $this->OutId = $OutId;
    }

    /**
     * 构建请求系统参数
     * @return array [type] [description]
     */
    private function commonParams(){
        return [
            'AccessKeyId'=>$this->app_key,
            'Timestamp'=>self::getTimestamp(),
            'Format'=>$this->format,
            'SignatureMethod'=>$this->signature_method,
            'SignatureVersion'=>'1.0',
            'SignatureNonce'=>$this->signature_nonce,
        ];
    }

    /**
     * @author bonzaphp@gmail.com
     * @return array
     */
    private function setCommonParams(){
           return ['Signature'=>$this->getSign()] + $this->commonParams();
    }
    /**
     * 构建请求业务参数
     */
    private function setPrivateParams(){
        return [
            'Action'=>$this->action,
            'Version'=>'2017-05-25',
            'RegionId'=>'cn-hangzhou',
            'SignName'=>$this->signName,
            'PhoneNumbers'=>$this->phone,
            'TemplateCode'=>$this->template_id,
            'TemplateParam'=>$this->setTemplateParam(),
             'OutId' =>$this->OutId,
        ];
    }

    public function setTemplateParam(){
        $this->template_param = "{\"code\":\"$this->code\"}";
        return $this->template_param;
    }

    /**
     * 设置签名
     * @author bonzaphp@gmail.com
     * @param $signName
     */
    public function setSignName($signName){
        $this->signName = $signName;
    }

    /**
     * 签名
     * @author bonzaphp@gmail.com
     * @return string
     */
    public function getSign(){
        $params = $this->commonParams() + $this->setPrivateParams();
        ksort($params);
        foreach ($params as $k => &$v) {
            if (!empty($v)) {
                urlencode($k);
                urlencode($v);
            }else{
                unset($params[$k]);
            }
        }
        $stringQuery = http_build_query($params);
        $stringQuery = $this->aliSmsEncode($stringQuery);
        $str_sms = $this->request_method.'&' .urlencode('/').'&'.$this->aliSmsEncode(urlencode($stringQuery));
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
     * @return mixed [type]      [description]
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
     * @return string
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

    /**
     * 获取当前时间的格式化显示
     * @author bonzaphp@gmail.com
     * @return false|string
     */
    static private function getTimestamp(){
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
     * @return void [type]              []
     */
    public function sendSms(){
        // $this->getSign();
        $params = array_merge($this->commonParams(),$this->setPrivateParams());
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
        $result = $this->httpGet($url);
        // echo $result;die;
        $res = json_decode($result,true);
        print_r($res);
        // $gets = $this->http_post(self::TARGET,$post_data);
    }

    //短信发送方法
    private function Post($data) {
        $url_info = parse_url(self::TARGET);
        $httpHeader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpHeader .= "Host:" . $url_info['host'] . "\r\n";
        $httpHeader .= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpHeader .= "Content-Length:" . strlen($data) . "\r\n";
        $httpHeader .= "Connection:close\r\n\r\n";
        //$httpHeader .= "Connection:Keep-Alive\r\n\r\n";
        $httpHeader .= $data;

        $fd = fsockopen($url_info['host'], 80);
        fwrite($fd, $httpHeader);
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
     * @return bool|mixed
     */
    private function httpGet($url){
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
    private function httpPost($url, $param, $post_file=false){
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
            $aPOST = [];
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
