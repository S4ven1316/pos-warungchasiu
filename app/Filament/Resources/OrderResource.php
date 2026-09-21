<?php

namespace App\Filament\Resources;

use Dom\Text;
use Filament\Forms;
use Filament\Tables;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Customer;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\OrderResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\OrderResource\RelationManagers;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
                    ->prefix('Data: '),
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
                        TextInput::make('phone')
                        ->disabled(),
                        TextInput::make('address')
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
                        }),
                        TextInput::make('price')
                        ->disabled(),
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
                        }),
                        TextInput::make('subtotal')
                    ])->columns(4),
                ]),
                
                Forms\Components\TextInput::make('total_price')
                    ->required()
                    ->numeric()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->sortable(),
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
            //
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
