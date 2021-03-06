<?php
 
namespace backend\components;
 
use Yii;
use Yii\helpers\ArrayHelper;
use backend\models\Action;
use backend\models\RoleMenu;
use backend\models\Role;
use backend\models\User;

class AccessRule extends \yii\filters\AccessRule 
{

    /**
     * Match Role Function
     * Fungsi ini untuk memberikan akses pada setiap menu
     * berdasarkan ROLE user
     * 
     * @param  user      Session Login User
     * 
     * @throws    ForbiddenException 
     * 
     * @return true|false (allow|forbidden)
     * 
     * @inheritdoc
     */
    protected function matchRole($user)
    {

        if (empty($this->roles)) {
            return true;
        }

        foreach ($this->roles as $role) {

            if ($role === '?') {
                
                if ($user->getIsGuest()) {
                    return true;
                }

            } elseif ($role === '@') {

                if (!$user->getIsGuest()) {

                    /**
                     * Check role user, bila bukan admin maka
                     * akan difilter aksesnya.
                     * Dan admin/role(30) bebas akses apa aja.
                     */
                    if ( $user->identity->role != User::ROLE_ADMIN )
                    {
                        
                        $role   = Role::find()
                            ->where(['=', 'code', $user->identity->role])->one();

                        $controllerCode   = Yii::$app->controller->code;
                        $controllerAction = Yii::$app->controller->action->id;
                        $roleMenu = RoleMenu::find()
                            ->joinWith('menu',    'menu.id   = role_menu.menu_id')
                            ->joinWith('action',  'action.id = role_menu.action_id')
                            ->where(['=',    'role_menu.role_id', $role->id])
                            ->andWhere(['=', 'menu.code', $controllerCode])
                            ->andWhere(['=', 'action.name', $controllerAction])
                            ->one();
                        if ( empty( $roleMenu ) )
                        {
                            return false;
                        }
                        
                    }
                    return true;

                }

            // Check if the user is logged in, and the roles match
            } elseif (!$user->getIsGuest() && $role === $user->identity->role) {
                return true;
            }
        }
 
        return false;
    }

    public static function getRoleActions()
    {
        $roleCode = !Yii::$app->user->getIsGuest() ? Yii::$app->user->identity->role : null;
        if ( empty( $roleCode ) ) return [ 'actions' => false, 'allow' => true, 'roles' => ['@'] ];

        $role   = Role::find() ->where(['=', 'code', Yii::$app->user->identity->role])->one();
        $action = Action::find()
            ->select('action.id, action.name')
            ->joinWith('roleMenu',  'action.id = role_menu.action_id')
            ->leftJoin('menu',     'menu.id = role_menu.menu_id')
            ->where(['=',    'role_menu.role_id', $role->id])
            ->andWhere([ '=','menu.code', Yii::$app->controller->code])
            ->asArray()
            ->all();

        $actions   = ArrayHelper::getColumn($action, 'name');
        if ( empty( $actions ) ) return [ 'actions' => false, 'allow' => true, 'roles' => ['@'] ];

        return [ 
            'actions' => $actions,
            'allow' => true,
            'roles' => [$roleCode]
        ];
    }

    /**
     * [getActions]
     * Untuk mendapatkan action apa saja yang bisa digunakan.
     * Biasanya return dari function ini untuk menentukan tombol apa 
     * saja yang diaktifkan. Seperti di halaman index pada module
     * 
     * @param  [integer] $roleCode [roleCode berdasarkan user role yang sedang digunakan]
     * @return [array]
     */
    public static function getActions( $roleCode )
    {
        // var_dump(Yii::$app->requestedRoute);exit;
        $session = Yii::$app->session;
        switch ( $roleCode ) {

            case User::ROLE_ADMIN:
                
                $sessionActions = $session->get('user.actions');

                if ( empty( $sessionActions ) )
                {
                    $action = Action::fetch()->asArray()->all();
                    $name   = ArrayHelper::getColumn($action, 'name');
                    $result = $session->set('user.actions', $name);
                } else {
                    $result = $session->get('user.actions');
                }
                return $result;
            
            break;
            case User::ROLE_MODERATOR:
            case User::ROLE_USER:

                $sessionActions = $session->get('user.actions');

                if ( empty( $sessionActions ) )
                {
                    $role   = Role::find()
                        ->where(['=', 'code', Yii::$app->user->identity->role])->one();
                    $action = Action::find()
                        ->select('action.id, action.name')
                        ->joinWith('roleMenu',  'action.id = role_menu.action_id')
                        ->leftJoin('menu',     'menu.id = role_menu.menu_id')
                        ->where(['=',    'role_menu.role_id', $role->id])
                        ->asArray()
                        ->all();

                    $name   = ArrayHelper::getColumn($action, 'name');
                    
                    $result = $session->set('user.actions', $name);
                } else {
                    $result = $session->get('user.actions');
                }
                return $result;
            break;
            
            default:
                return [ 'index' ];
            break;
        }
    }

    public static function actionAccess( $actions, $roleCode )
    {

        $getActions = static::getActions( $roleCode );
        $result = [];
        foreach( $actions as $action )
        {
            if ( in_array( $action, $getActions ) )
            {
                $result[$action] = true;
            }
        }
        // var_dump($getActions);exit;
        return $result;
    }

}