<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class SubsProduct extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:subs-product';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $connection = new AMQPStreamConnection(env('MQ_HOST'), env('MQ_PORT'), env('MQ_USER'), env('MQ_PASS'), env('MQ_VHOST'));
        $channel = $connection->channel();
        $callback = function ($msg) {
            $data = json_decode($msg->body, true);

            $product = Product::find($data['id']);
            $product->update([
                'name' => $data['name'],
                'category' => $data['category'],
                'description' => $data['description'],
                'price' => $data['price'],
            ]);
           
            echo ' [x] Product berhasil diupdate ', $msg->body, "\n";
        };

        $callbackDelete = function ($msg) {
            $data = json_decode($msg->body, true);

            $product = Product::find($data['id']);
            $product->delete();
           
            echo ' [x] Product berhasil didelete ', $msg->body, "\n";
        };
        $channel->queue_declare('product_update_queue', false, false, false, false);
        $channel->basic_consume('product_update_queue', '', false, true, false, false, $callback);

        $channel->queue_declare('product_delete_queue', false, false, false, false);
        $channel->basic_consume('product_delete_queue', '', false, true, false, false, $callbackDelete);
        echo 'Waiting for new message on test_queue', " \n";
        while ($channel->is_consuming()) {
            $channel->wait();
        }
        $channel->close();
        $connection->close();
    }
}
