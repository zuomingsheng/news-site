<?php
namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use app\common\model\User as UserModel;

class InitAdmin extends Command
{
    protected function configure()
    {
        $this->setName('init-admin')
            ->addArgument('password', Argument::OPTIONAL, '管理员密码', 'admin123')
            ->setDescription('初始化管理员账号');
    }

    protected function execute(Input $input, Output $output)
    {
        $password = $input->getArgument('password');

        $admin = UserModel::where('is_admin', 1)->find();

        if ($admin) {
            $admin->setAttr('password', $password);
            $admin->save();
            $output->writeln("更新管理员 {$admin->username} 密码成功: {$password}");
        } else {
            UserModel::create([
                'username'  => 'admin',
                'password'  => $password,
                'nickname'  => '管理员',
                'email'     => 'admin@example.com',
                'status'    => 1,
                'is_admin'  => 1,
            ]);
            $output->writeln("创建管理员账号成功: admin / {$password}");
        }
    }
}
