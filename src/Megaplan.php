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
    protected $https = true;


    public function __construct()
    {
        $this->api = config('megaplan.api');
        $this->host = config('megaplan.host');
        $this->login = config('megaplan.login');
        $this->password = config('megaplan.password');
        $this->accessId = config('megaplan.accessId');
        $this->secretKey = config('megaplan.secretKey');
        $this->https = config('megaplan.https');

        if($this->api) {
            // Авторизуемся в Мегаплане
            $this->req = new SdfApi_Request( '', '', $this->host, $this->https );

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

        $this->req = new SdfApi_Request( $this->accessId, $this->secretKey, $this->host, $this->https );
    }

    /**
     * *************  СДЕЛКИ  *************
     */

    /**
     * Получаем список схем сделок
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
     * Получаем список сделок
     *
     * @param null $filetrId - integer	Идентификатор фильтра
     * @param null $filters - array Массив параметров для фильтрации в формате поле => значение
     * @param null $limit
     * @param null $offset
     * @return mixed|string
     */
     public function getDeals($filterId = null, $filters = null, $limit = null, $offset = null)
     {
         $this->params = [];
         $this->params['FilterId'] = $filterId;
         $this->params['FilterFields'] = $filters;
         if(!is_null($limit)){
             $this->params['Limit'] = $limit;
         };

         if(!is_null($offset)){
             $this->params['Offset'] = $offset;
         };

         $raw = $this->req->get('/BumsTradeApiV01/Deal/list.api',$this->params);
         $raw = json_decode($raw);

         return $raw;
     }

    /**
     * Получаем карточку сделки
     *
     * @param $id - integer ID сделки
     * @param $fields - array Запрашиваемые поля ( меняет набор полей по умолчанию )
     * @param $extraFields - array Дополнительные поля ( дополняют набор полей по умолчанию )
     * @param null $limit
     * @param null $offset
     * @return mixed|string
     */
     public function getCardDeal($id, $fields = null, $extraFields = null)
     {
         $this->params = [];
         $this->params['Id'] = $id;
         $this->params['RequestedFields'] = $fields;
         $this->params['ExtraFields'] = $extraFields;

         $raw = $this->req->get('/BumsTradeApiV01/Deal/card.api',$this->params);
         $raw = json_decode($raw);

         return $raw;
     }

    /**
     * Получаем список полей для сделки
     * @param $id - integer ID сделки
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
     * @param $programId - integer ID схемы сделки
     * @param null $statusId - integer ID статуса сделки
     * @param bool|true $stricLogic - boolean Строгая логика перехода из статуса в статус. По умолчанию: true.
     * @param null $managerId - integer Идентификатор пользователя, являющегося менеджером сделки
     * @param $contractorId - integer Идентификатор клиента
     * @param $contactId - integer Идентификатор контактного лица
     * @param $auditors - string Идентификаторы пользователей являющихся аудиторами по сделке (перечисляем через запятую)
     * @param $description - string Описание сделки
     * @param $customs - array Дополнительное поле, созданное пользователем <название поля> => <значение>,
     *      $array['Category1000067CustomFieldNomerZakupki'] = $deal->purchaseNumber;
     *      $array['Category1000067CustomFieldSummaZakupki][Value'] = $deal->maxPrice;
     *      $array['Category1000067CustomFieldSummaZakupki][Currency'] = 1;
     * @param $positions - array Массив позиций сделок
     *
     * @return mixed|string
     */
    public function addDeal(
        $programId,
        $statusId = null,
        $stricLogic = true,
        $managerId = null,
        $contractorId = null,
        $contactId = null,
        $auditors = null,
        $description,
        $customs,
        $positions
    )
    {
        $this->params = [];

        $this->params['ProgramId'] = $programId;
        $this->params['StatusId'] = $statusId;
        $this->params['StrictLogic'] = $stricLogic;
        $this->params['Model[Manager]'] = $managerId;
        $this->params['Model[Contractor]'] = $contractorId;
        $this->params['Model[Contact]'] = $contactId;
        $this->params['Model[Auditors]'] = $auditors;
        $this->params['Model[Description]'] = $description;
        foreach ($customs as $key => $value) {
            $this->params['Model['.$key.']'] = $value;
        }
        $this->params['Positions'] = $positions;

        $raw = $this->req->post('/BumsTradeApiV01/Deal/save.api',$this->params);
        $raw = json_decode($raw);

        return $raw;
    }

    /**
     * Редактирование сделки
     *
     * @param $id - integer ID сделки
     * @param null $statusId - integer ID статуса сделки
     * @param bool|true $stricLogic - boolean Строгая логика перехода из статуса в статус. По умолчанию: true.
     * @param null $managerId - integer Идентификатор пользователя, являющегося менеджером сделки
     * @param $contractorId - integer Идентификатор клиента
     * @param $contactId - integer Идентификатор контактного лица
     * @param $auditors - string Идентификаторы пользователей являющихся аудиторами по сделке (перечисляем через запятую)
     * @param $description - string Описание сделки
     * @param $customs - array Дополнительное поле, созданное пользователем <название поля> => <значение>,
     *      $array['Category1000067CustomFieldNomerZakupki'] = $deal->purchaseNumber;
     *      $array['Category1000067CustomFieldSummaZakupki][Value'] = $deal->maxPrice;
     *      $array['Category1000067CustomFieldSummaZakupki][Currency'] = 1;
     * @param $positions - array Массив позиций сделок
     *
     * @return mixed|string
     */
     public function editDeal(
        $id,
        $statusId = null,
        $stricLogic = true,
        $managerId = null,
        $contractorId = null,
        $contactId = null,
        $auditors = null,
        $description,
        $customs,
        $positions
    )
    {
        $this->params = [];

        $this->params['Id'] = $id;
        $this->params['StatusId'] = $statusId;
        $this->params['StrictLogic'] = $stricLogic;
        $this->params['Model[Manager]'] = $managerId;
        $this->params['Model[Contractor]'] = $contractorId;
        $this->params['Model[Contact]'] = $contactId;
        $this->params['Model[Auditors]'] = $auditors;
        $this->params['Model[Description]'] = $description;
        foreach ($customs as $key => $value) {
            $this->params['Model['.$key.']'] = $value;
        }
        $this->params['Positions'] = $positions;

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
     * Карточка сотрудника
     * @param $id - ID сотрудника
     */
    public function employeeCard($id)
    {
        $this->params = [];
        $this->params['Id'] = $id;

        $raw = $this->req->get('/BumsStaffApiV01/Employee/card.api',$this->params);
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
            array $fields = null,
            $ignore = false
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
        $this->params['IgnoreRequiredFields']=$ignore;

        $raw = $this->req->get('/BumsCrmApiV01/Contractor/save.api',$this->params);
        $raw = json_decode($raw);

        return $raw;
    }

    /**
     * Редактирование клиента
     *
     * @param $id - ID клиента
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
    public function editContractor(
        $id,
        $type,
        $firstName = null,
        $lastName = null,
        $middleName = null,
        $companyName = null,
        $parentCompany = null,
        $email = null,
        $phones = null,
        $site = null,
        $birthday = null,
        $responsibles = null,
        $attaches = null,
        array $fields = null,
        $ignore = false
    )
    {
        $this->params = [];
        $this->params['Id'] = $id;
        $this->params['Model[TypePerson]'] = $type;
        $this->params['Model[FirstName]'] = $firstName;
        $this->params['Model[LastName]'] = $lastName;
        $this->params['Model[MiddleName]'] = $middleName;
        $this->params['Model[CompanyName]'] = $companyName;
        $this->params['Model[ParentCompany]'] = $parentCompany;
        $this->params['Model[Email]'] = $email;
        $this->params['Model[Phones]'] = $phones;
        $this->params['Model[Site]'] = $site;
        $this->params['Model[Birthday]'] = $birthday;
        $this->params['Model[Responsibles]'] = $responsibles;
        $this->params['Model[Attaches][Add]'] = $attaches;
        if(!is_null($fields)){
            foreach($fields as $key=>$val){
                $this->params["Model[$key]"] = $val;
            }
        }
        $this->params['IgnoreRequiredFields']=$ignore;

        $raw = $this->req->get('/BumsCrmApiV01/Contractor/save.api',$this->params);
        $raw = json_decode($raw);

        return $raw;
    }

    /**
     * Список типов клиентов
     *
     * @return void
     */
    public function contractorType()
    {
        $this->params = [];


        $raw = $this->req->get('/BumsCrmApiV01/ContractorType/list.api',$this->params);
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

    /**
    *-------------- ЗАДАЧИ ------------
    */

    /**
    * Список задач
    *
    *@param string $folder - Допустимые значения: 'incoming' - входящие, 'responsible' - ответственный,
    *                        'executor' - соисполнитель, 'owner' - исходящие, 'auditor' - аудируемые,
    *                        'my' - участник, 'all' - все. По умолчанию: all
    *@param string $timeUpdated - Возвращать только те объекты, которые были изменены после указанный даты. Дата/время в одном из форматов ISO 8601
    *@param string $status - Допустимые значения: 'actual' - актуальные, 'inprocess' - в процессе,
    *                        'new' - новые, 'overdue' - просроченные, 'done' - условно завершенные,
    *                        'delayed' - отложенные, 'completed' - завершенные, 'failed' - проваленные,
    *                        'any' - любые. По умолчанию: any
    *@param integer $favoritesOnly - Только избранное. Допустимые значения: 0, 1. По умолчанию: 0
    *@param string $search - Строка поиска показывать в списке задач все поля из карточки задачи/.
    *                        Допустимые значения: true, false По умолчанию: false
    *@param bool $onlyActual - Если true, то будут выводиться только незавершенные задачи
    *@param string $filterId - Код фильтра. Допустимые значения: любая строка (может быть как числом, так и строковым идентификатором)
    *@param bool $count - Если передан этот параметр со значением true, то вместо списка будет выводиться
    *                     только количество задач, удовлетворяющих условиям. По умолчанию: false
    *@param integer $employeeId - Код сотрудника, для которого нужно загрузить задачи
    *@param integer $projectId - Возвращает только задачи, входящие в проект ProjectId
    *@param integer $superTaskId - Возвращает только задачи, входящие в надзадачу SuperTaskId
    *@param string $sortBy - Сортировка результата. Допустимые значения: 'id' - идентификатор,
    *                        'name' - наименование, 'activity' - активность, 'deadline' - дата дедлайна,
    *                        'responsible' - ответственный, 'owner' - постановщик, 'contractor' - заказчик,
    *                        'start' - старт, 'plannedFinish' - плановый финиш, 'plannedWork' - запланировано,
    *                        'actualWork' - отработано, 'completed' - процент завершения, 'bonus' - бонус,
    *                        'fine' - штраф, 'plannedTime' - длительность
    *@param string $sortOrder - Направление сортировки. Допустимые значения: 'asc' - по возрастанию,
    *                           'desc' - по убыванию. По умолчанию: asc
    *@param bool $showActions - Нужно ли показывать в списке возможные действия над задачей. По умолчанию: false
    *@param integer $limit - Сколько выбрать задач (LIMIT), Целочисленное значение в диапазоне [1,100] По умолчанию: 50
    *@param integer $offset -
    */

    public function taskList(
        $folder = 'all',
        $timeUpdated = null,
        $status = 'any',
        $favoritesOnly = 0,
        $search = false,
        $onlyActual = false,
        $filterId = null,
        $count = false,
        $employeeId = null,
        $projectId = null,
        $superTaskId = null,
        $sortBy = null,
        $sortOrder = 'asc',
        $showActions = false,
        $limit = 50,
        $offset = null
    ){
        $this->params['Folder'] = $folder;
        $this->params['TimeUpdated'] = $timeUpdated;
        $this->params['Status'] = $status;
        $this->params['FavoritesOnly'] = $favoritesOnly;
        $this->params['Search'] = $search;
        $this->param['OnlyActual'] = $onlyActual;
        $this->params['FilterId'] = $filterId;
        $this->params['Count'] = $count;
        $this->params['EmployeeId'] = $employeeId;
        $this->params['ProjectId'] = $projectId;
        $this->params['SuperTaskId'] = $superTaskId;
        $this->params['SortBy'] = $sortBy;
        $this->params['SortOrder'] = $sortOrder;
        $this->params['ShowActions'] = $showActions;
        $this->params['Limit'] = $limit;
        $this->params['Offset'] = $offset;

        $raw = $this->req->get('/BumsTaskApiV01/Task/list.api',$this->params);
        $raw = json_decode($raw);

        return $raw;
    }

    /**
    * Создание задачи
    *
    *@param string $name - Название. Обязательное поле
    *@param datetime $deadline - Дедлайн (дата со временем)
    *@param date $deadlineDate - Дедлайн (только дата)
    *@param string $deadlineType - Тип дедлайна
    *@param integer $responsible - Код ответственного. Обязательное поле для не массовой задачи
    *@param integer[] $executors - Коды соисполнителей 	Обязательное поле для массовой задачи
    *@param integer[] $auditors - Коды аудиторов
    *@param integer $severity - Код важности
    *@param string $superTask - Код надзадачи (если число) или код проекта (если строка в формате ‘pКод_проекта‘)
    *@param integer $customer - Код заказчика
    *@param integer $isGroup - Массовая задача (каждому соисполнителю будет создана своя задача). Допустимые значения: 0 или 1
    *@param string $statement - Суть задачи
    *@param array $files - Массив приложенных файлов. Должен передаваться POST-запросом
    *@param datetime $start - Планирование: старт.Дата со временем
    *@param date $plannedFinish - Планирование: финиш. Только дата. При указанном Model[PlannedTime] расчитывается автоматически
    *@param integer $plannedTime - Планирование: длительность (в днях). При указанном Model[PlannedFinish] расчитывается автоматически
    *@param integer $plannedWork - Планирование: плановые трудозатраты (в минутах)
    */
    public function taskCreate(
        $name,
        $deadline = null,
        $deadlineDate = null,
        $deadlineType = null,
        $responsible = null,
        $executors = null,
        $auditors = null,
        $severity = null,
        $superTask = null,
        $customer = null,
        $isGroup = 0,
        $statement = null,
        $files = null,
        $start = null,
        $plannedFinish = null,
        $plannedTime = null,
        $plannedWork = null
    )
    {
        $this->params = [];
        $this->params['Model[Name]'] = $name;
        $this->params['Model[Deadline]'] = $deadline;
        $this->params['Model[DeadlineDate]'] = $deadlineDate;
        $this->params['Model[DeadlineType]'] = $deadlineType;
        $this->params['Model[Responsible]'] = $responsible;
        $this->params['Model[Executors]'] = $executors;
        $this->params['Model[Auditors]'] = $auditors;
        $this->params['Model[Severity]'] = $severity;
        $this->params['Model[SuperTask]'] = $superTask;
        $this->params['Model[Customer]'] = $customer;
        $this->params['Model[IsGroup]'] = $isGroup;
        $this->params['Model[Statement]'] = $statement;
        $this->params['Model[Attaches][Add]'] = $files;
        $this->params['Model[Start]'] = $start;
        $this->params['Model[PlannedFinish]'] = $plannedFinish;
        $this->params['Model[PlannedTime]'] = $plannedTime;
        $this->params['Model[PlannedWork]'] = $plannedWork;

        $raw = $this->req->get('/BumsTaskApiV01/Task/create.api',$this->params);
        $raw = json_decode($raw);

        return $raw;
    }

    /**
    * Редактирование задачи
    *
    *@param integer $id - ID задачи обязательный параметр.
    *@param string $name - Название
    *@param datetime $deadline - Дедлайн (дата со временем)
    *@param date $deadlineDate - Дедлайн (только дата)
    *@param string $deadlineType - Тип дедлайна
    *@param integer $owner - Код постановщика
    *@param integer $responsible - Код ответственного
    *@param integer[] $executors - Коды соисполнителей
    *@param integer[] $auditors - Коды аудиторов
    *@param integer $severity - Код важности
    *@param string $superTask - Код надзадачи (если число) или код проекта (если строка в формате ‘pКод_проекта‘)
    *@param integer $customer - Код заказчика
    *@param string $statement -
    *@param array $files - Массив приложенных файлов 	Должен передаваться POST-запросом
    *@param datetime $start - Планирование: старт 	Дата со временем
    *@param date $plannedFinish - Планирование: финиш 	Только дата. При указанном Model[PlannedTime] расчитывается автоматически
    *@param integer $plannedTime - Планирование: длительность (в днях) 	При указанном Model[PlannedFinish] расчитывается автоматически
    */
    public function taskEdit(
        $id,
        $name = null,
        $deadline = null,
        $deadlineDate = null,
        $deadlineType = null,
        $owner = null,
        $responsible = null,
        $executors = null,
        $auditors = null,
        $severity = null,
        $superTask = null,
        $customer = null,
        $statement = null,
        $files = null,
        $start = null,
        $plannedFinish = null,
        $plannedTime = null
    ){
        $this->params = [];
        $this->params['Id'] = $id;
        $this->params['Model[Name]'] = $name;
        $this->param['Model[Deadline]'] = $deadline;
        $this->params['Model[DeadlineDate]'] = $deadlineDate;
        $this->params['Model[DeadlineType]'] = $deadlineType;
        $this->params['Model[Owner]'] = $owner;
        $this->param['Model[Responsible]'] = $responsible;
        $this->param['Model[Executors]'] = $executors;
        $this->params['Model[Auditors]'] = $auditors;
        $this->params['Model[Severity]'] = $severity;
        $this->params['Model[SuperTask]'] = $superTask;
        $this->params['Model[Customer]'] = $customer;
        $this->params['Model[Statement]'] = $statement;
        $this->params['Model[Attaches][Add]'] = $files;
        $this->params['Model[Start]'] = $start;
        $this->params['Model[PlannedFinish]'] = $plannedFinish;
        $this->params['Model[PlannedTime]'] = $plannedTime;

        $raw = $this->req->get('/BumsTaskApiV01/Task/edit.api',$this->params);
        $raw = json_decode($raw);

        return $raw;
    }

     /**
     * --------- Глобальный поиск -----------
     */

    /**
     * Глобальный поиск
     */
    public function searchQuick($qs)
    {
        $this->params = [];
        $this->params['qs'] = $qs;

        $raw = $this->req->get('/BumsCommonApiV01/Search/quick.api',$this->params);
        $raw = json_decode($raw);

        return $raw;
    }
}
