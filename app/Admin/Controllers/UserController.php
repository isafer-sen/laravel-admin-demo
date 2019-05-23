<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ChinaArea;
use App\Models\User;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;

class UserController extends Controller
{
    use HasResourceActions;

    /**
     * @var string
     */
    protected $title = 'Users';

    /**
     * Display a listing of the resource.
     *
     * @return Content
     */
    protected function index(Content $content)
    {
        return $content
            ->header($this->title)
            ->description('Index')
            ->body($this->grid());
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->header($this->title)
            ->description('Detail')
            ->body($this->detail($id));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header($this->title)
            ->description('Edit')
            ->body($this->form()->edit($id));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header($this->title)
            ->description('Create')
            ->body($this->form());
    }

    public function grid()
    {
        $grid = new Grid(new User());

        $grid->model()->with('profile')->orderBy('id', 'DESC');

        $grid->paginate(20);

        $grid->id('ID')->sortable();

        $grid->name()->editable();

        $grid->column('expand')->expand(function () {

            if (empty($this->profile)) {
                return '';
            }

            $profile = array_only($this->profile->toArray(), ['homepage', 'gender', 'birthday', 'address', 'last_login_at', 'last_login_ip', 'lat', 'lng']);

            return new Table([], $profile);

        }, 'Profile');
//
        $grid->column('position')->openMap(function () {

            return [$this->profile['lat'], $this->profile['lng']];

        }, 'Position');

        $grid->column('profile.homepage')->urlWrapper();

        $grid->email()->prependIcon('envelope');

        //$grid->profile()->mobile()->prependIcon('phone');

        //$grid->column('profile.age')->progressBar(['success', 'striped'], 'xs')->sortable();

        $grid->profile()->age()->sortable();
        $grid->profile()->gender()->using(['f' => '女', 'm' => '男']);

        $grid->created_at();

        $grid->updated_at();

        $grid->filter(function (Grid\Filter $filter) {

            $filter->disableIdFilter();

            $filter->like('name');

            $filter->equal('address.province_id', 'Province')
                ->select(ChinaArea::province()->pluck('name', 'id'))
                ->load('address.city_id', '/demo/api/china/city');

            $filter->equal('address.city_id', 'City')->select()
                ->load('address.district_id', '/demo/api/china/district');

            $filter->equal('address.district_id', 'District')->select();

            $filter->scope('male')->whereHas('profile', function ($query) {
                $query->where('gender', 'm');
            });
            $filter->scope('female')->whereHas('profile', function ($query) {
                $query->where('gender', 'f');
            });

            $filter->scope('all_gender', 'Male & Female')->whereHas('profile', function ($query) {
                $query->whereIn('gender', ['f', 'm']);
            });

            $filter->scope('children')->whereHas('profile', function ($query) {
                $query->where('age', '<', 18);
            });

            $filter->scope('children')->whereHas('profile', function ($query) {
                $query->where('age', '<', 18);
            });

            $filter->scope('elderly')->whereHas('profile', function ($query) {
                $query->where('age', '>', 60);
            });
        });

        $grid->actions(function (Grid\Displayers\Actions $actions) {

            if ($actions->getKey() % 2 == 0) {
                $actions->disableDelete();
                $actions->append('<a href=""><i class="fa fa-paper-plane"></i></a>');
            } else {
                $actions->disableEdit();
                $actions->prepend('<a href=""><i class="fa fa-paper-plane"></i></a>');
            }
        });

        return $grid;
    }

    public function detail($id)
    {
        $show = new Show(User::findOrFail($id));

        $show->name();
        $show->avatar()->file();
        $show->username();
        $show->email();

        $show->divider();

        $show->created_at();
        $show->updated_at();

        $show->profile(function ($profile) {

            $profile->homepage()->link();
            $profile->mobile();
            $profile->avatar();
            $profile->document();
            $profile->gender();
            $profile->birthday();
            $profile->address();
            $profile->color();
            $profile->age();
            $profile->last_login_at();
            $profile->last_login_ip();

            $profile->created_at();
            $profile->updated_at();

        });

        $show->sns(function ($sns) {

            $sns->qq();
            $sns->wechat();
            $sns->weibo();
            $sns->github();
            $sns->google();
            $sns->facebook();
            $sns->twitter();

            $sns->created_at();
            $sns->updated_at();

        });

        $show->address(function ($address) {

            $address->province()->name('省份');
            $address->city()->name('城市');
            $address->district()->name('地区');
            $address->address();


            $address->created_at();
            $address->updated_at();

        });

        $show->friends(function ($friend) {

            $friend->resource('users');

            $friend->name();
            $friend->email();

        });

        return $show;
    }

    public function form()
    {
        $form = new Form(new User);

        $form->model()->makeVisible('password');

        $form->tab('Basic', function (Form $form) {

            $form->display('id');

            //$form->input('name')->rules('required');

            $form->text('name')/*->rules('required')*/;
            $form->email('email')->rules('required');
            $form->display('created_at');
            $form->display('updated_at');

        })->tab('Profile', function (Form $form) {

            $form->url('profile.homepage');
            $form->ip('profile.last_login_ip');
            $form->datetime('profile.last_login_at');
            $form->color('profile.color')->default('#c48c20');
            $form->mobile('profile.mobile')->default(13524120142);
            $form->date('profile.birthday');

//                $form->map('profile.lat', 'profile.lng', 'Position')->useTencentMap();
            $form->slider('profile.age', 'Age')->options(['max' => 50, 'min' => 20, 'step' => 1, 'postfix' => 'years old']);
            $form->datetimeRange('profile.created_at', 'profile.updated_at', 'Time line');

        })->tab('Sns info', function (Form $form) {

            $form->text('sns.qq');
            $form->text('sns.wechat')->rules('required');
            $form->text('sns.weibo');
            $form->text('sns.github');
            $form->text('sns.google');
            $form->text('sns.facebook');
            $form->text('sns.twitter');
            $form->display('sns.created_at');
            $form->display('sns.updated_at');

        })->tab('Address', function (Form $form) {

            $form->select('address.province_id')->options(
                ChinaArea::province()->pluck('name', 'id')
            )
                ->load('address.city_id', '/demo/api/china/city')
                ->load('test', '/demo/api/china/city');

            $form->select('address.city_id')->options(function ($id) {
                return ChinaArea::options($id);
            })->load('address.district_id', '/demo/api/china/district');

            $form->select('address.district_id')->options(function ($id) {
                return ChinaArea::options($id);
            });

            $form->text('address.address');

        })->tab('Password', function (Form $form) {

            $form->password('password')->rules('confirmed');
            $form->password('password_confirmation');

        });

        $form->ignore(['password_confirmation']);

        return $form;
    }
}
