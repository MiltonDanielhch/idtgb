<div class="mb-8">
    <div class="flex justify-between mb-2">
        <span class="text-sm font-medium text-gray-700">Paso {{ $current_step }} de {{ $total_steps }}</span>
        <span class="text-sm font-medium text-gray-700">{{ $progress }}% Completado</span>
    </div>
    <div class="w-full bg-gray-200 rounded-full h-2.5">
        <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $progress }}%"></div>
    </div>
</div>
