<?php

namespace App\Admin\Controllers;

use App\Models\ChatMessage;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ChatMessageController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Chat Messages';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ChatMessage());
        $grid->model()->orderBy('id', 'desc');
        $grid->quickSearch('body', 'sender_name', 'receiver_name', 'status');
        $grid->column('id', __('Id'))->sortable();
        $grid->column('created_at', __('Created'))->sortable()
            ->display(function ($createdAt) {
                return date('Y-m-d H:i:s', strtotime($createdAt));
            });
        $grid->column('sender_name', __('Sender name'))->sortable();
        $grid->column('sender_photo', __('Sender photo'))->lightbox(['width' => 50, 'height' => 50]);
        $grid->column('receiver_name', __('Receiver name'))->sortable();
        $grid->column('receiver_photo', __('Receiver photo'))->lightbox(['width' => 50, 'height' => 50]);
        $grid->column('body', __('Body'))->sortable();
        $grid->column('type', __('Type'))->sortable();
        $grid->column('status', __('Status'))->sortable();
        return $grid;
        $grid->column('audio', __('Audio'))->sortable();
        $grid->column('video', __('Video'))->sortable();
        $grid->column('document', __('Document'))->sortable();
        $grid->column('photo', __('Photo'))->lightbox(['width' => 50, 'height' => 50]);
        $grid->column('longitude', __('Longitude'));
        $grid->column('latitude', __('Latitude'));
        $grid->column('message_reactions', __('Message reactions'));
        $grid->column('reply_to_message_id', __('Reply to message id'));
        $grid->column('is_forwarded', __('Is forwarded'));
        $grid->column('delivery_status', __('Delivery status'));
        $grid->column('read_at', __('Read at'));
        $grid->column('edited_at', __('Edited at'));
        $grid->column('deleted_at', __('Deleted at'));
        $grid->column('message_metadata', __('Message metadata'));
        $grid->column('media_duration', __('Media duration'));
        $grid->column('media_size', __('Media size'));
        $grid->column('media_thumbnail', __('Media thumbnail'));
        $grid->column('location_name', __('Location name'));
        $grid->column('location_address', __('Location address'));

    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(ChatMessage::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('chat_head_id', __('Chat head id'));
        $show->field('sender_id', __('Sender id'));
        $show->field('receiver_id', __('Receiver id'));
        $show->field('sender_name', __('Sender name'));
        $show->field('sender_photo', __('Sender photo'));
        $show->field('receiver_name', __('Receiver name'));
        $show->field('receiver_photo', __('Receiver photo'));
        $show->field('body', __('Body'));
        $show->field('type', __('Type'));
        $show->field('status', __('Status'));
        $show->field('audio', __('Audio'));
        $show->field('video', __('Video'));
        $show->field('document', __('Document'));
        $show->field('photo', __('Photo'));
        $show->field('longitude', __('Longitude'));
        $show->field('latitude', __('Latitude'));
        $show->field('message_reactions', __('Message reactions'));
        $show->field('reply_to_message_id', __('Reply to message id'));
        $show->field('is_forwarded', __('Is forwarded'));
        $show->field('delivery_status', __('Delivery status'));
        $show->field('read_at', __('Read at'));
        $show->field('edited_at', __('Edited at'));
        $show->field('deleted_at', __('Deleted at'));
        $show->field('message_metadata', __('Message metadata'));
        $show->field('media_duration', __('Media duration'));
        $show->field('media_size', __('Media size'));
        $show->field('media_thumbnail', __('Media thumbnail'));
        $show->field('location_name', __('Location name'));
        $show->field('location_address', __('Location address'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new ChatMessage());

        $form->number('chat_head_id', __('Chat head id'));
        $form->number('sender_id', __('Sender id'));
        $form->number('receiver_id', __('Receiver id'));
        $form->textarea('sender_name', __('Sender name'));
        $form->textarea('sender_photo', __('Sender photo'));
        $form->textarea('receiver_name', __('Receiver name'));
        $form->textarea('receiver_photo', __('Receiver photo'));
        $form->textarea('body', __('Body'));
        $form->text('type', __('Type'));
        $form->text('status', __('Status'));
        $form->textarea('audio', __('Audio'));
        $form->textarea('video', __('Video'));
        $form->textarea('document', __('Document'));
        $form->textarea('photo', __('Photo'));
        $form->text('longitude', __('Longitude'));
        $form->text('latitude', __('Latitude'));
        $form->text('message_reactions', __('Message reactions'));
        $form->number('reply_to_message_id', __('Reply to message id'));
        $form->text('is_forwarded', __('Is forwarded'))->default('No');
        $form->text('delivery_status', __('Delivery status'))->default('sent');
        $form->datetime('read_at', __('Read at'))->default(date('Y-m-d H:i:s'));
        $form->datetime('edited_at', __('Edited at'))->default(date('Y-m-d H:i:s'));
        $form->text('message_metadata', __('Message metadata'));
        $form->number('media_duration', __('Media duration'));
        $form->number('media_size', __('Media size'));
        $form->textarea('media_thumbnail', __('Media thumbnail'));
        $form->text('location_name', __('Location name'));
        $form->textarea('location_address', __('Location address'));

        return $form;
    }
}
