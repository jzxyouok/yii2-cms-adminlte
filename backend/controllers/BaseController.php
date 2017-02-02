<?php
namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use backend\models\LoginForm;
use backend\models\User;
use backend\components\AccessRule;
use backend\components\MenuComponent;

/**
 * Site controller
 */
class BaseController extends Controller
{

    public $title  = 'Yii2 AdminLTE v1.0.0';
    public $layout = 'main.twig';
    public $menu   = 'dashboard';
    public $menuChild = '';
    public $controller;

    public $session, $userData, $user, $description;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                // We will override the default rule config with the new AccessRule class
                'ruleConfig' => [
                    'class' => AccessRule::className(),
                ],
                'rules' => [
                    [
                        'actions' => ['login', 'error', 'register'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index', 'detail', 'list-of-data'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['create'],
                        'allow' => true,
                        // Allow users, moderators and admins to create
                        'roles' => [
                            User::ROLE_USER,
                            User::ROLE_MODERATOR,
                            User::ROLE_ADMIN
                        ],
                    ],
                    [
                        'actions' => ['update'],
                        'allow' => true,
                        // Allow moderators and admins to update
                        'roles' => [
                            User::ROLE_MODERATOR,
                            User::ROLE_ADMIN
                        ],
                    ],
                    [
                        'actions' => ['delete'],
                        'allow' => true,
                        // Allow admins to delete
                        'roles' => [
                            User::ROLE_ADMIN
                        ],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    // 'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    public function init()
    {

        $app = Yii::$app;
        $this->session = $app->session;
        $this->user = $app->user;

        /** Check, apakah ada session user atau tidak */
        if ( !empty($this->user->identity) )
        {

            $view = $this->view;
            $view->title = $this->title;
            $view->params['title']       = $this->title;
            $view->params['description'] = $this->description;
            $view->params['menuCurrent'] = $this->menu;
            $view->params['menuChildCurrent'] = $this->menuChild;
        }

    }
    
    public function actionErrorss()
    {
        exit;
        $exception = Yii::$app->errorHandler->exception;
        if ($exception !== null) {
            return $this->render('error', ['exception' => $exception]);
        }
    }
}