<?php
/**
 * Модель для работы с АПИ Мегаплана
 */

namespace Zloykolobok\Megaplan;

use Zloykolobok\Megaplan\SdfApi_Request;
use Illuminate\Support\Facades\Config;


class Megaplan
{
    protected $req;
    protected $params = [];
    protected $api = false;
    protected $host = null;
    protected $login = null;
    protected $password = null;
    protected $accessId = null;
    protected $secretKey = null;


    public function __construct()
    {
        $this->api = config('megaplan.api');
        $this->host = config('megaplan.host');
        $this->login = config('megaplan.login');
        $this->password = config('megaplan.password');
        $this->accessId = config('megaplan.accessId');
        $this->secretKey = config('megaplan.secretKey');

        if($this->api) {
            // Авторизуемся в Мегаплане
            $this->req = new SdfApi_Request( '', '', $this->host, true );
        
            $response = json_decode(
                $this->req->get(
                    '/BumsCommonApiV01/User/authorize.api',
                    array(
                        'Login' => $this->login,
                        'Password' => md5( $this->password )
                    )
                )
            );
            if($response->status->code == 'ok'){
                // Получаем AccessId и SecretKey
                $this->accessId = $response->data->AccessId;
                $this->secretKey = $response->data->SecretKey;
                unset( $this->req );
            } else {
                dd('Error: '. $response->status->message);
            }
        
            
        }
        
        $this->req = new SdfApi_Request( $this->accessId, $this->secretKey, $this->host, true );
    }

    /**
     * *************  СДЕЛКИ  *************
     */

    /**
     * Получаем список сделок
     *
     * @param null $limit
     * @param null $offset
     * @return mixed|string
     */
    public function getSchemes($limit = null, $offset = null)
    {
        $this->params = [];
        if(!is_null($limit)){
            $this->params['Limit'] = $limit;
        };

        if(!is_null($offset)){
            $this->params['Offset'] = $offset;
        };

        $raw = $this->req->get('/BumsTradeApiV01/Program/list.api',$this->params);
        $raw = json_decode($raw);

        return $raw;
    }

    /**
     * Получаем список полей для сделки
     * @param $id - ID сделки
     * @return mixed|string
     */
    public function getSchemeFields($id)
    {
        $this->params = [];
        $this->params['ProgramId'] = $id;
        $raw = $this->req->get('/BumsTradeApiV01/Deal/listFields.api',$this->params);
        $raw = json_decode($raw);

        return $raw;
    }

    /**
     * Добавление сделки
     *
     * @param $programId
     * @param null $statusId
     * @param bool|true $stricLogic
     * @param null $managerId
     * @param $contractorId
     * @param $contactId
     * @param $description
     * @param $type
     * @param array|null $files
     * @return mixed|string
     */
    public function addDeal(
        $programId,
        $statusId = null,
        $stricLogic = true,
        $managerId = null,
        $contractorId,
        $contactId,
        $description,
        $type,
        array $files = null,
        $prioritet,
        $number = null,
        $statusClienta
    )
    {
        $this->params = [];
//        Category1000047CustomFieldTipZayavki
//        Category1000047CustomFieldFayli

        // ProgramId
        // StatusId = null
        // StrictLogic = true
        // Model[Manager]
        // Model[Contractor]
        // Model[Contact]
        // Model[Auditors] = null
        // Model[Description]
        // Model[Paid][...]
        // Model[Paid][Value]
        // Model[Paid][Rate]
        // Model[Paid][Currency]
        // Model[Cost][...]
        // Model[Cost][Value]
        // Model[Cost][Rate]
        // Model[Cost][Rate]
        // Model[Имя_поля][Add]
        // Model[Имя_поля][Add][0...n][Content]
        // Model[Имя_поля][Add][0...n][Name]
        // Model[Имя_поля][Delete][0...n]
        // Model[Имя_поля]
        // Positions
        $this->params['ProgramId'] = $programId;
        $this->params['StatusId'] = $statusId;
        $this->params['StrictLogic'] = $stricLogic;
        $this->params['Model[Contractor]'] = $contractorId;
        $this->params['Model[Contact]'] = $contactId;
        $this->params['Model[Description]'] = $description;
        $this->params['Model[Category1000047CustomFieldTipZayavki]'] = $type;
        $this->params['Model[Category1000047CustomFieldFayli][Add]'] = $files;
        $this->params['Model[Category1000047CustomFieldPrioritet]'] = $prioritet;
        $this->params['Model[Category1000047CustomFieldNomerZayavki]'] = $number;
        $this->params['Model[Category1000047CustomFieldStatusKlienta]'] = $statusClienta;

        $raw = $this->req->post('/BumsTradeApiV01/Deal/save.api',$this->params);
        $raw = json_decode($raw);

        return $raw;
    }

    /**
     * *************  СОТРУДНИКИ  *************
     */

    /**
     * Получаем список сотрудников
     *
     * @param null $departamentId - ID департамента
     * @param null $orderBy - параметры сортировки. Допустимые значения: "name", "departament", "position"
     * @param null $timeUpdated - При указании возвращать только те объекты, которые были изменены после этой даты
     * @param null $name - Фильтрация по части имени сотрудника
     * @return mixed|string
     */
    public function getEmployees($departamentId = null, $orderBy = null, $timeUpdated = null, $name = null)
    {
        $this->params = [];
        if(!is_null($departamentId)){
            $this->params['Department'] = (int)$departamentId;
        }

        if(!is_null($orderBy)){
            $this->params['OrderBy'] = $orderBy;
        }

        if(!is_null($timeUpdated)){
            $this->params['TimeUpdated'] = $timeUpdated;
        }

        if(!is_null($name)){
            $this->params['Name'] = $name;
        }

        $raw = $this->req->get('/BumsStaffApiV01/Employee/list.api',$this->params);
        $raw = json_decode($raw);

        return $raw;
    }

    /**
     * *************  КЛИЕНТЫ  *************
     */

    /**
     * Получаем список полей клиента
     *
     * @return mixed|string
     */
    public function getContractorFields()
    {
        $this->params = [];
        $raw = $this->req->get('/BumsCrmApiV01/Contractor/listFields.api',$this->params);
        $raw = json_decode($raw);

        return $raw;
    }

    /**
     * Получаем список клиентов
     *
     * @param null $limit - Сколько выбрать клиентов (LIMIT) Выборка происходит с начала от меньших ID к большим
     * @param null $offset - Начиная с какого выбирать клиента (OFFSET)
     * @param null $qs - Условие поиска
     * @param null $phone - Номер телефона в произвольном формате
     * @param array|null $model - Массив в формате имя поля => значение. Используется для фильтрации по конкретным значениям полей.
     * @return mixed|string
     */
    public function getContractorList($limit = null, $offset = null, $qs = null, $phone = null, array $model = null)
    {
        $this->params = [];
        if(!is_null($limit)){
            $this->params['Limit'] = (int)$limit;
        }

        if(!is_null($offset)){
            $this->params['Offset'] = (int)$offset;
        }

        if(!is_null($qs)){
            $this->params['qs'] = $qs;
        }

        if(!is_null($phone)){
            $this->params['Phone'] = $phone;
        }

        if(!is_null($model)){
            foreach ($model as $key=>$val ){
                $this->params['Model'][$key] = $val;
            }
        }

        $raw = $this->req->get('/BumsCrmApiV01/Contractor/list.api',$this->params);
        $raw = json_decode($raw);

        // dd($raw);

        return $raw;
    }

    /**
     * Получаем карточку клиента
     *
     * @param $id - ID клиента
     * @param null $fields - массив полей для вывода
     * @return mixed|string
     */
    public function getContractorById($id, $fields = null)
    {
        $this->params = [];
        if(!is_null($fields)){
            foreach ($fields as $val ){
                $this->params['RequestedFields'][] = $val;
            }
        }

        $this->params['Id'] = $id;

        $raw = $this->req->get('/BumsCrmApiV01/Contractor/card.api',$this->params);
        $raw = json_decode($raw);

        return $raw;
    }

    /**
     * Создание клиента
     *
     * @param $type - приниает human или company
     * @param null $firstName - имя
     * @param null $lastName - фамилия
     * @param null $middleName - отчество
     * @param null $companyName - название компании, если $type = company
     * @param null $parentCompany - ID компании
     * @param null $email -
     * @param null $phones -
     * @param null $birthday -
     * @param null $responsibles
     * @param null $attaches
     * @param null $fields - доп поля array('название поля' => 'значение')
     * @return mixed|string
     */
    public function addContractor(
            $type,
            $firstName = null,
            $lastName = null,
            $middleName = null,
            $companyName = null,
            $parentCompany = null,
            $email = null,
            $phones = null,
            $birthday = null,
            $responsibles = null,
            $attaches = null,
            array $fields = null
        )
    {
        $this->params = [];
        $this->params['Model[TypePerson]'] = $type;
        $this->params['Model[FirstName]'] = $firstName;
        $this->params['Model[LastName]'] = $lastName;
        $this->params['Model[MiddleName]'] = $middleName;
        $this->params['Model[CompanyName]'] = $companyName;
        $this->params['Model[ParentCompany]'] = $parentCompany;
        $this->params['Model[Email]'] = $email;
        $this->params['Model[Phones]'] = $phones;
        $this->params['Model[Birthday]'] = $birthday;
        $this->params['Model[Responsibles]'] = $responsibles;
        $this->params['Model[Attaches][Add]'] = $attaches;
        if(!is_null($fields)){
            foreach($fields as $key=>$val){
                $this->params["Model[$key]"] = $val;
            }
        }

        $raw = $this->req->get('/BumsCrmApiV01/Contractor/save.api',$this->params);
        $raw = json_decode($raw);

        return $raw;
    }

    /**
    *
    * ----------------- Комментарии -----------------
    *
    */

    /**
    * Список всех комментариев по актуальным задачам и проектам
    *
    *@param bool $onlyActual - Если true, то будут выводиться комментарии только незавершенных задач или проектов
    *@param string $timeUpdated - Дата/время в одном из форматов ISO 8601, Возвращать только те объекты, которые были изменены после указанный даты
    *@param bool $droppedOnly - Если true, то будут выводиться удаленные комментарии задач или проектов
    *
    *@return mixed|string
    */
    public function commentAll($onlyActual = true, $timeUpdated = null, $droppedOnly = false)
    {
        $this->params = [];
        $this->params['OnlyActula'] = $onlyActual;
        $this->params['TimeUpdated'] = $timeUpdated;
        $this->params['DroppedOnly'] = $droppedOnly;

        $raw = $this->req->get('/BumsCommonApiV01/Comment/all.api',$this->params);
        $raw = json_decode($raw);

        return $raw;
    }

    /**
    * Загрузка одного комментария по идентификатору
    *
    * @param int $id - ID комментария
    *
    * @return mixed|string
    */
    public function commentById($id)
    {
        $this->params = [];
        $this->params['Id'] = $id;

        $raw = $this->req->get('/BumsCommonApiV01/Comment/commentById.api',$this->params);
        $raw = json_decode($raw);

        return dd($raw);
    }

    /**
     * Создание комментария
     *
     * @param $subjectType - приниает task (задача), project (проект), contractor (клиент), deal (сделка), discuss (обсуждение)
     * @param $id - ID комментируемого объекта
     * @param $text - Текст комментария
     * @param $work - Кол-во потраченных минут, которое приплюсуется к комментируемому объекту (задача или проект)
     * @return mixed|string
     */
     public function commentCreate($subjectType = 'deal',$id,$text,$work=null)
     {
         $this->params = [];
         $this->params['SubjectType'] = $subjectType;
         $this->params['SubjectId'] = $id;
         $this->params['Model[Text]'] = $text;
         $this->params['Model[Work]'] = $work;
 
         $raw = $this->req->get('/BumsCommonApiV01/Comment/create.api',$this->params);
         $raw = json_decode($raw);
 
         return $raw;
     }

    /**
    *Список комментариев по задаче/проекту
    *@param string $subjectType - task (задача), project (проект), contractor (клиент), deal (сделка) Тип комментируемого объекта
    *@param int $subjectId - ID комментируемого объекта
    *@param string $timeUpdated - Дата/время в одном из форматов ISO 8601, Возвращать только те объекты, которые были изменены после указанный даты
    *@param string $order - asc (по возрастанию), desc (по убыванию), Направление сортировки по дате (по умолчанию asc)
    *@param bool $textHtml - Возвращать ли комментарий в Html формате (по умолчанию false)
    *@param bool $unreadOnly - Возвращает только непрочитанные комментарии если true, по умолчанию false
    *@param int $limit - Сколько выбрать комментариев (LIMIT)
    *@param int $offset - Начиная с какого выбирать комментарии (OFFSET)
    *@param bool $droppedOnly - Возвращать только удаленные комментарии
    */
    public function commentList(
        $subjectType = 'task',
        $subjectId,
        $timeUpdated = null, 
        $order = 'asc',
        $textHtml = false,
        $unreadOnly = false,
        $limit = null,
        $offset = null,
        $droppedOnly = false)
    {
        $this->params = [];
        $this->params['SubjectType'] = $subjectType;
        $this->params['SubjectId'] = $subjectId;
        $this->params['TimeUpdated'] = $timeUpdated;
        $this->params['Order'] = $order;
        $this->params['TextHtml'] = $textHtml;
        $this->params['UnreadOnly'] = $unreadOnly;
        $this->params['Limit'] = $limit;
        $this->params['Offset'] = $offset;
        $this->params['DroppedOnly'] = $droppedOnly;

        $raw = $this->req->get('/BumsCommonApiV01/Comment/list.api',$this->params);
        $raw = json_decode($raw);

        return $raw;
    }

    

}
