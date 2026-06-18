<?php
	
	namespace App\Http\Resources\Dashboard;
	
	use Illuminate\Http\Request;
	use Illuminate\Http\Resources\Json\JsonResource;
	
	class ProjectMiniResource extends JsonResource
	{
		/**
			* Transform the resource into an array.
			*
			* @return array<string, mixed>
		*/
		public function toArray(Request $request): array
		{
			return [
			'id' => (int) $this->id,
			'code' => $this->code,
			'name' => $this->name,
			'target_end_date' => $this->target_end_date,
			'progress' => (float) ($this->progress ?? 0),
			'status_code' => $this->status_code,
			];
		}
	}
