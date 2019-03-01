<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/5
 * Time: 15:42
 */

/**
 * 通过接口获取数据
 * Class zuber
 */
class zuber
{
    //private $url = "";
    private $header = array();

    //zuber
    public $url = "https://api.zuber.im/v3/rooms/search?city=%E4%B8%8A%E6%B5%B7&house=1&sex=&cost1=&cost2=&address=&start_time=&end_time=&longitude=&latitude=&subway_line=&subway_station=&short_rent=&type=&bed=true&room_type_affirm=&coords_type=gaode&sequence=";
    //public $url = "https://api.zuber.im/v3/views/room?room_id=";

    //合租趣
    private $hurl1 = "https://hzp.baihejia.com/chuzu?page=";
    private $hurl2 = "&pageSize=100&startRent=0&cityId=2&endRent=0&point=31.308218002319336%2C121.50479888916016&token=OzslQbZH68RyogGW89968&ver=1.5.8&pf=WEB&miniProgramAppId=wx8815157fa9e22eaf&blackBox=eyJvcyI6Ind4YXBwIiwidCI6ImlsRWpoWUpoYm5FMXNXM1J1RElzazJSUDZjRTlNeWdBQjhYNTJEMHFSdXVLdS9wWVNia3VYSGpsY2VxNGRTSVdLbFhPeTZISkxrNkI2eTJhVDk4MnZRPT0iLCJ2IjoicVhlQzU1RWFzU2tzS2ZsbGl2QU9EVz09IiwicGFydG5lciI6ImhlenVwIn0%253D";

    //暖房
    private $nurl1 = "https://nuan.io/get-room-results?searchType=text&city=sh&rentType=all&bedroomAll=true&sort=default&pageNo=";
    private $nurl2 = "&source=58&source=doubangroup&source=ganji&source=soufang&source=anjuke&source=nuan&source=smth";

    //嗨住
//    private $list1 = "https://m.hizhu.com/houselist.html?pageno=";
//    private $list2 = "&city_code=001009001&limit=10&sort=-1&money_max=999999&money_min=0&logicSort=0&region_id=&plate_id=&line_id=0&stand_id=0&type_no=0&search_id=&key=&key_self=0&latitude=&longitude=&distance=0&other_ids=&update_time=0";
//    private $detail1 = "https://m.hizhu.com/housedetail.html?city_code=001009001&room_id=";
//    private $dteail2 = "&customer_id=";


    public function __construct()
    {

    }

    /**
     * 设置认证信息
     * @return string
     */
    public function setAuthorization()
    {
        $timestamp = time();
        $oauth2 = md5($timestamp);
        //"request_url=" + c.API_VERSION + t + "&content=" + v + "&request_method=" + f + "&timestamp=" + o + "&secret=" + s
        $sign = "request_url=v3/rooms/search&content={}&request_method=get&timestamp={$timestamp}&secret=";
        //$sign = "request_url=v3/views/room&content={}&request_method=get&timestamp={$timestamp}&secret=";
        $signature = md5($sign);
        $scene = md5('web');
        return "timestamp={$timestamp};oauth2={$oauth2};signature={$signature};scene={$scene}";
    }

    /**
     * 设置header头信息(zuber)
     * @return array
     */
    public function setHeader()
    {
        return $this->header = array(
            "Authorization:".$this->setAuthorization(),
            "Content-type:application/json",
            "Referer: http://www.zuber.im/",
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36"
        );
    }

    /**
     * 设置url(zuber)
     * @return string
     */
    public function setUrl($time = '')
    {
        return $this->url = $this->url.(String)$time;
    }

    /**
     * 设置暖房的url
     * @param $page
     * @return string
     */
    public function setNuanUrl($page){
        if(!empty($this->url)){
            $this->url = "";
        }
        return $this->url = $this->nurl1.$page.$this->nurl2;
    }

    /**
     * 设置合租趣的url
     * @param $page
     * @return string
     */
    public function setHezuquUrl($page){
        if(!empty($this->url)){
            $this->url = "";
        }
        return $this->url = $this->hurl1.$page.$this->hurl2;
    }

    /**
     * 设置获取暖房业主联系方式的url
     * @param $roomId
     * @return string
     */
    public function setGetPhoneUrl($roomId){
        return $this->url = "https://nuan.io/api/room/".$roomId."/contact";
    }

    /**
     * 设置嗨住的url
     * @param $info
     * @return string
     */
//    public function setHaizhuUrl($info,$num = '1'){
//        if("list" == $info){
//            return $this->url = $this->list1.$num.$this->list2;
//        } else if("detail" == $info){
//            return $this->url = $this->detail1.$num.$this->dteail2;
//        } else {
//            return $this->url;
//        }
//    }


    /**
     * curl访问接口
     * @return mixed
     */
    public function zuberCurl()
    {
        $curl = curl_init();
        $url = $this->url;
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        //curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
        //zuber header
        //curl_setopt($curl, CURLOPT_HTTPHEADER,$this->setHeader());
        //暖房header
        if(!empty($this->header)){
            curl_setopt($curl,CURLOPT_HTTPHEADER,$this->header);
        }
        $res = json_decode(curl_exec($curl), true);
        //return $res['result'];
        return $res;
    }

    /**
     * 暖房header
     * @return array
     */
    public function setNuanHeader(){
        return $this->header = array(
            ":method:GET",
            ":scheme:https",
            ":authority:nuan.io",
            "accept:*/*",
            "content-type:application/json",
            "referer:https://nuan.io/get-room-results?searchType=text&city=sh&rentType=all&bedroomAll=true&sort=default&pageNo=1&source=58&source=doubangroup&source=ganji&source=soufang&source=anjuke&source=nuan&source=smth",
            "x-nuam-map-vendor:tencent",
            "x-nuan-ssid:7585230b643745faf630aed5a3a84ed40320650ce75b7008717b4f1d878c06b1cb025c8c4538d1202fd2593082fc8936",
            "x-nuan-platform:wechat"
        );

    }

}

