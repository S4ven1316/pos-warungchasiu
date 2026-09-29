<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Customer;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\OrderDetail;
use Filament\Resources\Resource;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use App\Filament\Resources\OrderResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Filament\Resources\OrderResource\RelationManagers\OrderDetailRelationManager;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DateTimePicker::make('date')
                    ->default(now())
                    ->required()
                    ->disabled()
                    ->hiddenLabel()
                    ->dehydrated()
                    ->prefix('Data: ')
                    ->columnSpanFull(),
                Group::make()
                ->schema([
                Forms\Components\Section::make()
                ->description('Customer Information')
                ->schema([
                    Forms\Components\Select::make('customer_id')
                        ->relationship('customer', 'name')
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function($state, Set $set){
                            $customer= Customer::find($state);
                            $set('phone', $customer->phone ?? null);
                            $set('address', $customer->address ?? null);
                        }),
                        Placeholder::make('phone')
                        ->content(fn(Get $get)=>Customer::find($get('customer_id'))?->phone ?? '-')
                        ->disabled(),
                        Placeholder::make('address')
                        ->content(fn(Get $get)=>Customer::find($get('customer_id'))?->address ?? '-')
                        ->disabled(),
                        
                ])->columns(3),

                Section::make()
                ->description('Order Details')
                ->schema([
                    Repeater::make('orderDetail')
                    ->relationship()
                    ->schema([
                        Select::make('product_id')
                        ->relationship('product', 'name')
                        ->reactive()
                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                        ->afterStateUpdated(function($state,Set $set, Get $get){
                            $product = Product::find($state);
                            $price = $product->price ?? 0;
                            $set('price', $price);
                            $qty = $get('qty') ?? 1;
                            $set ('qty', $qty);
                            $subtotal = $price * $qty;
                            $set('subtotal', $subtotal);

                            $items = $get('../../orderDetail') ?? [];
                            $total = collect($items)->sum(fn($item)=>$item['subtotal'] ?? 0);
                            $set('../../total_price', $total);

                            $discount = $get('../../discount') ?? 0;
                            $discount_ammount = $total * $discount / 100;
                            $set ('../../discount_ammount', $discount_ammount);
                            $set ('../../total_payment', $total - $discount_ammount);
                        }),
                        TextInput::make('price') 
                        ->numeric()  
                        ->disabled()
                        ->formatStateUsing(fn($state, Get $get)
                    =>$state ?? Product::find($get('product_id'))?->price ?? 0),
                        TextInput::make('qty')
                        ->numeric()
                        ->default(1)
                        ->reactive()
                        ->afterStateUpdated(function($state, Set $set, Get $get){
                            $price = $get('price') ?? 0;
                            $set('subtotal', $price * $state);

                            $items = $get('../../orderDetail') ?? [];
                            $total = collect($items)->sum(fn($item)=>$item['subtotal'] ?? 0);
                            $set('../../total_price', $total);
                            
                            $discount = $get('../../discount') ?? 0;
                            $discount_ammount = $total * $discount / 100;
                            $set ('../../discount_ammount', $discount_ammount);
                            $set ('../../total_payment', $total - $discount_ammount);
                        }),
                        TextInput::make('subtotal')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(),
                    ])->columns(4),
                ]),
                ])->columnSpan(2),

                Section::make()
                ->description('Payment Information')
                ->schema([
                    Select::make('status')
                    ->options([
                        'new' => 'New',
                        'proccesing' => 'Proccesing',
                        'canceled' => 'Canceled',
                        'completed' => 'Completed',
                    ])->default('new')
                    ->columnSpanFull(),
                    TextInput::make('total_price')
                    ->required()
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->columnSpanFull(),
                    TextInput::make('discount')
                    ->numeric()
                    ->default(0)
                    ->columnSpan(1)
                    ->reactive()
                    ->afterStateUpdated(function($state, Set $set, Get $get){
                        $discount=floatval($state)??0;
                        $total_price = $get('total_price')??0;
                        $discount_ammount = $total_price * $discount / 100;
                        $set ('discount_ammount', $discount_ammount);
                        $set ('total_payment', $total_price - $discount_ammount);
                        
                    }),
                    TextInput::make('discount_ammount')
                    ->numeric()
                    ->default(0)
                    ->columnSpan(3)
                    ->disabled()
                    ->dehydrated(),
                    TextInput::make('total_payment')
                    ->numeric()
                    ->default(0)
                    ->columnSpanFull()
                    ->disabled()
                    ->dehydrated(),
                ])->columnSpan(1)
                ->columns(4),
                
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')   
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            OrderDetailRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
