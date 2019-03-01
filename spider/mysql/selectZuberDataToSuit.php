<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/1/21
 * Time: 13:59
 */

class selectZuberDataToSuit
{
    private $host = '39.96.196.222';
    private $user = 'root';
    private $password = '123456';
    private $dbname = 'My_suit';
    private $pdo;

    //私有的静态变量
    private static $_instance = null;

    private function __construct()
    {

        $this->pdo = $this->PDOinit();
    }

    public static function getInstance(){
        if(is_null(self::$_instance)){
            return self::$_instance = new self();
        }
        return self::$_instance;

    }

    private function setConfig()
    {
        $db = array(
            'host' => $this->host,         //设置服务器地址
            'port' => '3306',              //设端口
            'dbname' => $this->dbname,             //设置数据库名
            'username' => $this->user,           //设置账号
            'password' => $this->password,      //设置密码
            'charset' => 'utf8',             //设置编码格式
            'dsn' => 'mysql:host='.$this->host.';dbname='.$this->dbname.';port=3306;charset=utf8',   //这里不知道为什么，也需要这样再写一遍。
        );

        return $db;
    }

    private function PDOinit(){
        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, //默认是PDO::ERRMODE_SILENT，0，（忽略错误模式）
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, //默认是PDO::FETCH_BOTH, 4
        );
        $db = $this->setConfig();
        try{
            $pdo = new PDO($db['dsn'],$db['username'],$db['password'],$options);
        }catch (PDOException $e){
            die("数据库连接失败：".$e->getMessage());
        }
        //或者更通用的设置属性方式：
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); //设置异常处理方式
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); //设置默认关联索引遍历

        return $pdo;
    }

    /**
     * 事物操作
     * @param $house
     * @param $user
     * @param $phone
     */
    public function transAction($house,$user,$phone){
        $pdo = $this->pdo;
        try{
            $pdo->beginTransaction();

            $res = $this->getUserIdByInfo($phone);
            if(empty($res)){
                $userId = $this->addDataToUserInfo($user,$phone);
            } else {
                $userId = $res['id'];
            }
            if($this->getHouseByInfo($userId,$house['road'],$house['localization'],$house['region'],$house['cost1'])){
                $houseId = $this->addDataToHouseInfo($house,$userId);
                $this->addDataToUserType($houseId);
                $this->addDataToFitment($houseId);
                $this->addDataToHouseImg($houseId,$userId,$house['photo']);
            }

            $pdo->commit();
        }catch (PDOException $e){
            $pdo->rollBack();
            die("事物出错：".$e->getMessage());
        }
    }

    /**
     * 判断房源是否已经存在
     * @param $userId
     * @param $road
     * @param $metro
     * @return bool
     */
    private function getHouseByInfo($userId,$road,$metro,$ward,$rent){
        $pdo = $this->pdo;
        try{
            $fetch = $pdo->query("select id from house_info where user_id = ".$userId." and road = '".$road."' and metro = '".$metro."' 
            and ward = '".$ward."' and rent = ".$rent);
            $id = $fetch->fetch();
        }catch (PDOException $e){
            die("getUserIdByInfo出错：".$e->getMessage());
        }
        if(empty($id)){
            return true;
        } else {
            return false;
        }

    }

    /**
     * 判断用户是否已经存
     * @param $phone
     * @return mixed
     */
    private function getUserIdByInfo($phone){
        $pdo = $this->pdo;
        try{
            $fetch = $pdo->query("select id from user_info where phone = ".$phone);
            $id = $fetch->fetch();
        }catch (PDOException $e){
            die("getUserIdByInfo出错：".$e->getMessage());
        }
        return $id;
    }

    /**
     *筛选符合条件的数据
     * @param $data
     * @return bool|string
     */
    public function findRightData($data){
        $phone = $this->getPhone($data['content']);
        if(empty($phone)){
            return false;
        }
        if($data['room_type_affirm'] == '长租公寓'){
            return false;
        }
        return $phone;
    }


    /**
     * 添加用户信息到user_info表
     * @param $user
     * @param $phone
     * @return string
     */
    private function addDataToUserInfo($user,$phone){
        $pdo = $this->pdo;
        try{
            $stmt = $pdo->prepare("insert into user_info VALUES (:id,:name,:caller, :phone, :avatar, :sex, :province, 
                                  :city, :openId, :identity, :status,:birthday,:career,:constellation)");
            $stmt->execute(array(
                ":id" => null,
                ":name" => $this->removeEmojiChar($user['username']),
                ":caller" => $this->removeEmojiChar($user['username']),
                ":phone" => $phone,
                ":avatar" => $user['avatar'],
                ":sex" => $this->getSex($user['sex']),
                ":province" => $user['born_province'],
                ":city" => $user['born_city'],
                ":openId" => '',
                ":identity" => 0,
                ":status" => 0,
                ":birthday" => '',
                ":career" => $user['profession'],
                ":constellation" => $user['xingzuo']
            ));
            $id = $pdo->lastInsertId();
        }catch (PDOException $e){
            die("user_info数据添加失败：".$e->getMessage());
        }
        return $id;
    }

    /**
     * 添加房源信息到house_info表
     * @param $house
     * @param $userId
     * @return string
     */
    private function addDataToHouseInfo($house,$userId){
        $pdo = $this->pdo;
        try{
            $stmt =$pdo->prepare("insert into house_info VALUES(:id, :user_id, :apply_id, :ward, :village, :road, 
                                    :door_num, :metro, :mall, :type, :area, :style, :tfloor, :floor, :face, :elevator, :pet, :adult, 
                                    :children, :share, :describes, :rent, :pay_way, :visit_time, :status, :zhonghe, 
                                    :suit, :payment, :easy, :time, :userMax, :masterId,:origin, :authentic)");
            $row = $stmt->execute(array(
                ":id" => null,
                ":user_id" => $userId,
                ":apply_id" => 0,
                ":ward" => $house['region'],
                ":village" => '',
                ":road" => $house['road'],
                ":door_num" => "",
                ":metro" => $house['localization'],
                ":mall" => '',
                ":type" => $this->getType($house['bed_count'], $house['hall_count'], $house['bathroom_count']),
                ":area" => '',
                ":style" => 0,
                ":tfloor" => 0,
                ":floor" =>0,
                ":face" => "",
                ":elevator" => 0,
                ":pet" => 1,
                ":adult" => 10,
                ":children" => 10,
                ":share" => 2,
                ":describes" => $this->removeEmojiChar($house['content']),
                ":rent" => $house['cost1'],
                ":pay_way" => '00,押一付一',
                ":visit_time" => "",
                ":status" => 0,
                ":zhonghe" => 0,
                ":suit" => 0,
                ":payment" => 0,
                ":easy" => 0,
                ":time" => $house['last_modify_time'],
                ":userMax" => 0,
                ":masterId" => 7,
                ":origin" => "zuber",
                ":authentic" => 0,
            ));
            $id = $pdo->lastInsertId();
        }catch (PDOException $e){
            die("house_info数据添加失败：".$e->getMessage());
        }
        return $id;
    }

    /**
     * 添加用户类型到user_type
     * @param $houseId
     */
    private function addDataToUserType($houseId){
        $pdo = $this->pdo;
        try{
            $stmt = $pdo->prepare("insert into user_type VALUES(:id,:house_id, :single, :couple, :family, :friend,:user_other)");
            $stmt->execute(array(
                ":id" => null,
                ":house_id" => $houseId,
                ":single" => 1,
                ":couple" => 1,
                ":family" => 1,
                ":friend" => 1,
                ":user_other" => 1,

            ));

        }catch (PDOException $e){
            die("user_type添加失败:".$e->getMessage());
        }
    }

    /**
     * 添加设施到fitment表
     * @param $houseId
     */
    private function addDataToFitment($houseId){
        $pdo = $this->pdo;
        try{
            $stmt = $pdo->prepare("insert into fitment VALUES(:id, :house_id, :balcony, :bed, :bedstand, :chair, :cooker,
                                   :desk, :garden, :hearth, :kongt, :locker, :mattres, :fridge, :sofa, :toilet, :tv,
                                   :cupboard, :washer, :water, :wifi, :fit_other)");
            $stmt->execute(array(
                ":id" => null,
                ":house_id" => $houseId,
                ":balcony" => 0,
                ":bed" => 1,
                ":bedstand" => 0,
                ":chair" => 0,
                ":cooker" => 0,
                ":desk" => 0,
                ":garden" => 0,
                ":hearth" => 0,
                ":kongt" => 0,
                ":locker" => 0,
                ":mattres" => 0,
                ":fridge" => 0,
                ":sofa" => 0,
                ":toilet" => 1,
                ":tv" => 0,
                ":cupboard" => 0,
                ":washer" => 1,
                ":water" => 1,
                ":wifi" => 0,
                ":fit_other" => 1,
            ));

        }catch (PDOException $e){
            die("fitment数据添加失败：".$e->getMessage());
        }
    }

    /**
     * 添加房源图片到house_img
     * @param $houseId
     * @param $userId
     * @param $photo
     */
    private function addDataToHouseImg($houseId,$userId,$photo){
        $pdo = $this->pdo;
        try{
            $stmt = $pdo->prepare("insert into house_img VALUES(:id,:user_id,:house_id,:image)");
            $stmt->execute(array(
                ":id"=>null,
                ":user_id"=>$userId,
                ":house_id"=>$houseId,
                ":image"=>$photo,
            ));
        }catch (PDOException $e){
            die("house_img添加失败:".$e->getMessage());
        }
    }

    /**
     * 处理字段type
     * @param $bed
     * @param $hall
     * @param $bathroom
     * @return string
     */
    private function getType($bed,$hall,$bathroom){
        $str = "";
        switch ($bed){
            case 1: $str.= '一室'; break;
            case 2: $str.= '二室'; break;
            case 3: $str.= '三室'; break;
            case 4: $str.= '四室'; break;
            case 5: $str.= '五室'; break;
            default: $str.= '一室';
        }
        switch ($hall){
            case 1: $str .= '一厅'; break;
            case 2: $str .= '二厅'; break;
            case 3: $str .= '三厅'; break;
        }
        switch ($bathroom){
            case 1: $str .= '一卫'; break;
            case 2: $str .= '一卫'; break;
            case 3: $str .= '一卫'; break;
        }
        return $str;
    }

    /**
     * 处理字段sex
     * @param $sex
     * @return int
     */
    private function getSex($sex){
        if('m' == $sex){
            return 1;
        } else if('f' == $sex){
            return 0;
        } else {
            return 2;
        }
    }


    /**
     * 获取手机号
     * @param $oldStr
     * @return string
     */
    private function getPhone($oldStr)
    {
        // 检测字符串是否为空
        $oldStr=trim($oldStr);
        $numbers = '';
        if(empty($oldStr)){
            return $numbers;
        }

        $num='/^((13[0-9])|(14[5,7])|(15[0-3,5-9])|(17[0,3,5-8])|(18[0-9])|166|198|199|(147))\d{8}$/';
        $reg = '/\d{11}/';
        preg_match_all($reg,$oldStr,$result);
        if(empty($result)){
            return $numbers;
        }

        foreach ($result[0] as $res){
            preg_match($num, $res, $val);
            if(!empty($val) && $val[0]){
                $numbers = $val[0];
                break;
            }
        }

        return $numbers;
    }

    /**
     * 处理emoji表情
     * @param $str
     * @return string
     */
    private function removeEmojiChar($str)
    {
        $mbLen = mb_strlen($str);

        $strArr = [];
        for ($i = 0; $i < $mbLen; $i++) {
            $mbSubstr = mb_substr($str, $i, 1, 'utf-8');
            if (strlen($mbSubstr) >= 4) {
                continue;
            }
            $strArr[] = $mbSubstr;
        }

        return implode('', $strArr);
    }

}