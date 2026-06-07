<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('resetPassword')
                ->label('重置密码')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->modalHeading(fn (): string => '重置 '.$this->record->name.' 的密码')
                ->modalSubmitActionLabel('确认重置')
                ->modalWidth('md')
                ->form([
                    Forms\Components\TextInput::make('password')
                        ->label('新密码')
                        ->password()
                        ->revealable()
                        ->required()
                        ->minLength(8)
                        ->extraInputAttributes(['autocomplete' => 'new-password']),
                    Forms\Components\TextInput::make('password_confirmation')
                        ->label('确认新密码')
                        ->password()
                        ->revealable()
                        ->required()
                        ->rules(['same:password'])
                        ->extraInputAttributes(['autocomplete' => 'new-password']),
                ])
                ->action(function (array $data): void {
                    $this->record->forceFill([
                        'password' => $data['password'],
                    ])->save();

                    Notification::make()
                        ->title('密码已重置')
                        ->success()
                        ->send();
                }),
            Actions\DeleteAction::make()
                ->visible(fn (): bool => UserResource::canDelete($this->record)),
        ];
    }
}
