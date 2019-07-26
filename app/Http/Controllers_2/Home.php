<?php

namespace App\Http\Controllers_2;

use App\Http\Business\TestBusiness;
use App\Http\Facades\TestBusiness as StaticTestBusiness;
use Shy\Http\Session;
use Shy\Http\Contracts\Request;
use Shy\Core\Container;

class Home
{
    public function index(TestBusiness $business, Session $session)
    {
        if ($business->isMobile()) {
            $info = 'Hello World in Mobile';
        } else {
            $info = 'Hello World';
        }

        if ($session->exist('user')) {
            $info .= ' Again';
        } else {
            $session->set('user', 1);
        }

        $title = 'Shy Framework';

        return view('home', compact('title', 'info'))->layout('main');
    }

    public function smarty(Session $session)
    {
        if (StaticTestBusiness::isMobile()) {
            $params['info'] = 'Hello World in Mobile';
        } else {
            $params['info'] = 'Hello World';
        }

        if ($session->exist('user')) {
            $params['info'] .= ' Again';
        } else {
            $session->set('user', 1);
        }

        $params['title'] = 'Shy Framework';

        $params['shy'] = Container::getContainer();

        return smarty('smarty.tpl', $params);
    }

    public function test(Request $request)
    {
        return 'controller echo test ' . json_encode($request->all());
    }

}
