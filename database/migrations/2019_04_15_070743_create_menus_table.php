<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMenusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('parent_id')->comment('父级菜单');
            $table->string('icon',50)->comment('图标');
            $table->string('uri',191)->unique()->comment('路由地址');
            $table->string('guard_name',30)->comment('守卫');
            $table->string('menu_name',191)->comment('菜单名称');
            $table->string('permission_name',50)->unique()->comment('权限名');
            $table->smallInteger('menu_type')->comment('菜单类型 1:父级菜单 2:子菜单 3:功能节点');
            $table->tinyInteger('status')->default(1)->comment('菜单状态  0：停用 1：启用');
            $table->integer('sort')->comment('排序');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menus');
    }
}
