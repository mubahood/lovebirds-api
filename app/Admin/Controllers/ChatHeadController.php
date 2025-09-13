<?php

namespace App\Admin\Controllers;

use App\Models\ChatHead;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ChatHeadController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Chat Head';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ChatHead());
        $grid->model()->orderBy('id', 'desc');
        $grid->quickSearch('product_name', 'product_owner_name', 'customer_name', 'last_message_body');

        $grid->column('id', __('Id'))->editable();
        $grid->column('created_at', __('Created at'));
        $grid->column('product_owner_id', __('Receiver'));
        $grid->column('product_owner_name', __('Receiver owner name'))->editable();
        $grid->column('product_owner_photo', __('Receiver owner photo'))->lightbox(['width' => 50, 'height' => 50]);
        //customer_id
        $grid->column('customer_id', __('Sender'))->sortable()->editable();
        $grid->column('customer_name', __('Sender name'))->sortable()->editable();
        $grid->column('customer_photo', __('Sender photo'))->lightbox(['width' => 50, 'height' => 50]);
        $grid->column('last_message_body', __('Last message body'))->sortable()->editable();
        $grid->column('last_message_status', __('Last message status'))->sortable()->editable();
        //messages count
        $grid->column('messages_count', __('Messages Count'))->display(function () {
            return \App\Models\ChatMessage::where('chat_head_id', $this->id)->count();
        });
        return $grid;
        $grid->column('type', __('Type'));
        $grid->column('sender_unread_count', __('Sender unread count'));
        $grid->column('receiver_unread_count', __('Receiver unread count'));
        $grid->column('is_typing_customer', __('Is typing customer'));
        $grid->column('is_typing_owner', __('Is typing owner'));
        $grid->column('match_id', __('Match id'));
        $grid->column('is_blocked', __('Is blocked'));
        $grid->column('blocked_by_customer', __('Blocked by customer'));
        $grid->column('blocked_by_owner', __('Blocked by owner'));
        $grid->column('conversation_started_at', __('Conversation started at'));
        $grid->column('last_typing_activity', __('Last typing activity'));
        $grid->column('chat_metadata', __('Chat metadata'));
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(ChatHead::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('product_id', __('Product id'));
        $show->field('product_name', __('Product name'));
        $show->field('product_photo', __('Product photo'));
        $show->field('product_owner_id', __('Product owner id'));
        $show->field('product_owner_name', __('Product owner name'));
        $show->field('product_owner_photo', __('Product owner photo'));
        $show->field('product_owner_last_seen', __('Product owner last seen'));
        $show->field('customer_id', __('Customer id'));
        $show->field('customer_name', __('Customer name'));
        $show->field('customer_photo', __('Customer photo'));
        $show->field('customer_last_seen', __('Customer last seen'));
        $show->field('last_message_body', __('Last message body'));
        $show->field('last_message_time', __('Last message time'));
        $show->field('last_message_status', __('Last message status'));
        $show->field('type', __('Type'));
        $show->field('sender_unread_count', __('Sender unread count'));
        $show->field('receiver_unread_count', __('Receiver unread count'));
        $show->field('is_typing_customer', __('Is typing customer'));
        $show->field('is_typing_owner', __('Is typing owner'));
        $show->field('match_id', __('Match id'));
        $show->field('is_blocked', __('Is blocked'));
        $show->field('blocked_by_customer', __('Blocked by customer'));
        $show->field('blocked_by_owner', __('Blocked by owner'));
        $show->field('conversation_started_at', __('Conversation started at'));
        $show->field('last_typing_activity', __('Last typing activity'));
        $show->field('chat_metadata', __('Chat metadata'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new ChatHead());

        $form->number('product_id', __('Product id'));
        $form->textarea('product_name', __('Product name'));
        $form->textarea('product_photo', __('Product photo'));
        $form->number('product_owner_id', __('Product owner id'));
        $form->textarea('product_owner_name', __('Product owner name'));
        $form->textarea('product_owner_photo', __('Product owner photo'));
        $form->text('product_owner_last_seen', __('Product owner last seen'));
        $form->number('customer_id', __('Customer id'));
        $form->textarea('customer_name', __('Customer name'));
        $form->textarea('customer_photo', __('Customer photo'));
        $form->text('customer_last_seen', __('Customer last seen'));
        $form->textarea('last_message_body', __('Last message body'));
        $form->text('last_message_time', __('Last message time'));
        $form->text('last_message_status', __('Last message status'));
        $form->text('type', __('Type'))->default('dating');
        $form->number('sender_unread_count', __('Sender unread count'));
        $form->number('receiver_unread_count', __('Receiver unread count'));
        $form->switch('is_typing_customer', __('Is typing customer'));
        $form->switch('is_typing_owner', __('Is typing owner'));
        $form->number('match_id', __('Match id'));
        $form->switch('is_blocked', __('Is blocked'));
        $form->switch('blocked_by_customer', __('Blocked by customer'));
        $form->switch('blocked_by_owner', __('Blocked by owner'));
        $form->datetime('conversation_started_at', __('Conversation started at'))->default(date('Y-m-d H:i:s'));
        $form->datetime('last_typing_activity', __('Last typing activity'))->default(date('Y-m-d H:i:s'));
        $form->text('chat_metadata', __('Chat metadata'));

        return $form;
    }
}
