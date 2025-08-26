# Sprint 5: Sports Service - Parte 1 (Gestión Básica Deportiva)

**Duración:** 2 semanas  
**Fase:** 2 - Módulos Financiero y Deportivo Básico  
**Objetivo:** Implementar la gestión básica del módulo deportivo con categorías, jugadores y entrenamientos

## Resumen del Sprint

Este sprint inicia el módulo deportivo implementando la gestión de categorías, registro de jugadores, programación de entrenamientos y control básico de asistencia.

## Objetivos Específicos

- ✅ Implementar gestión de categorías deportivas
- ✅ Crear sistema de registro de jugadores
- ✅ Desarrollar programación de entrenamientos
- ✅ Implementar control de asistencia básico
- ✅ Crear dashboard deportivo inicial

## Tareas Detalladas

### 1. Configuración del Sports Service

**Responsable:** Backend Developer Senior  
**Estimación:** 1 día  
**Prioridad:** Alta

#### Subtareas:

1. **Crear estructura del microservicio:**
   ```bash
   # Crear nuevo proyecto Laravel
   composer create-project laravel/laravel sports-service
   cd sports-service
   
   # Instalar dependencias
   composer require tymon/jwt-auth
   composer require spatie/laravel-permission
   composer require league/fractal
   ```

2. **Configurar base de datos:**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=mysql-sports
   DB_PORT=3306
   DB_DATABASE=sports_service
   DB_USERNAME=sports_user
   DB_PASSWORD=sports_password
   ```

3. **Configurar JWT y middleware:**
   ```php
   // config/jwt.php - Configuración JWT
   // app/Http/Middleware/JWTAuth.php - Middleware de autenticación
   // routes/api.php - Rutas protegidas
   ```

4. **Crear estructura de carpetas:**
   ```
   app/
   ├── Http/
   │   ├── Controllers/
   │   │   ├── CategoryController.php
   │   │   ├── PlayerController.php
   │   │   ├── TrainingController.php
   │   │   └── AttendanceController.php
   │   ├── Requests/
   │   ├── Resources/
   │   └── Middleware/
   ├── Models/
   │   ├── Category.php
   │   ├── Player.php
   │   ├── Training.php
   │   └── Attendance.php
   ├── Services/
   └── Repositories/
   ```

#### Criterios de Aceptación:
- [ ] Microservicio Sports configurado
- [ ] Base de datos conectada
- [ ] JWT funcionando
- [ ] Estructura de carpetas creada

---

### 2. Sistema de Categorías Deportivas

**Responsable:** Backend Developer  
**Estimación:** 2 días  
**Prioridad:** Alta

#### Subtareas:

1. **Crear modelo Category:**
   ```php
   // Migration: create_categories_table
   Schema::create('categories', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('school_id');
       $table->string('name'); // Sub-8, Sub-10, Sub-12, etc.
       $table->text('description')->nullable();
       $table->integer('min_age');
       $table->integer('max_age');
       $table->enum('gender', ['male', 'female', 'mixed']);
       $table->integer('max_players')->default(25);
       $table->json('training_days'); // ["monday", "wednesday", "friday"]
       $table->time('training_start_time');
       $table->time('training_end_time');
       $table->string('field_location')->nullable();
       $table->boolean('is_active')->default(true);
       $table->unsignedBigInteger('coach_id')->nullable();
       $table->timestamps();
       
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('coach_id')->references('id')->on('users');
       $table->index(['school_id', 'is_active']);
   });
   ```

2. **Implementar modelo Category:**
   ```php
   class Category extends Model
   {
       protected $fillable = [
           'school_id', 'name', 'description', 'min_age', 'max_age',
           'gender', 'max_players', 'training_days', 'training_start_time',
           'training_end_time', 'field_location', 'is_active', 'coach_id'
       ];
       
       protected $casts = [
           'training_days' => 'array',
           'training_start_time' => 'datetime:H:i',
           'training_end_time' => 'datetime:H:i',
           'is_active' => 'boolean'
       ];
       
       // Relaciones
       public function school() {
           return $this->belongsTo(School::class);
       }
       
       public function coach() {
           return $this->belongsTo(User::class, 'coach_id');
       }
       
       public function players() {
           return $this->hasMany(Player::class);
       }
       
       public function trainings() {
           return $this->hasMany(Training::class);
       }
       
       // Scopes
       public function scopeActive($query) {
           return $query->where('is_active', true);
       }
       
       public function scopeByGender($query, $gender) {
           return $query->where('gender', $gender);
       }
       
       // Métodos auxiliares
       public function canAcceptPlayer($birthDate) {
           $age = Carbon::parse($birthDate)->age;
           return $age >= $this->min_age && $age <= $this->max_age;
       }
       
       public function hasAvailableSpots() {
           return $this->players()->count() < $this->max_players;
       }
   }
   ```

3. **Crear CategoryController:**
   ```php
   class CategoryController extends Controller
   {
       public function index(Request $request) {
           $categories = Category::with(['coach', 'players'])
               ->where('school_id', $request->user()->school_id)
               ->when($request->active, fn($q) => $q->active())
               ->when($request->gender, fn($q, $gender) => $q->byGender($gender))
               ->paginate(15);
               
           return CategoryResource::collection($categories);
       }
       
       public function store(StoreCategoryRequest $request) {
           $category = Category::create([
               'school_id' => $request->user()->school_id,
               ...$request->validated()
           ]);
           
           return new CategoryResource($category->load(['coach', 'players']));
       }
       
       public function show(Category $category) {
           $this->authorize('view', $category);
           
           return new CategoryResource($category->load([
               'coach', 'players', 'trainings' => fn($q) => $q->recent()
           ]));
       }
       
       public function update(UpdateCategoryRequest $request, Category $category) {
           $this->authorize('update', $category);
           
           $category->update($request->validated());
           
           return new CategoryResource($category->load(['coach', 'players']));
       }
       
       public function destroy(Category $category) {
           $this->authorize('delete', $category);
           
           if ($category->players()->exists()) {
               return response()->json([
                   'message' => 'No se puede eliminar una categoría con jugadores asignados'
               ], 422);
           }
           
           $category->delete();
           
           return response()->json(['message' => 'Categoría eliminada exitosamente']);
       }
   }
   ```

4. **Crear validaciones:**
   ```php
   class StoreCategoryRequest extends FormRequest
   {
       public function rules() {
           return [
               'name' => 'required|string|max:100',
               'description' => 'nullable|string|max:500',
               'min_age' => 'required|integer|min:4|max:25',
               'max_age' => 'required|integer|min:4|max:25|gte:min_age',
               'gender' => 'required|in:male,female,mixed',
               'max_players' => 'required|integer|min:10|max:50',
               'training_days' => 'required|array|min:1',
               'training_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
               'training_start_time' => 'required|date_format:H:i',
               'training_end_time' => 'required|date_format:H:i|after:training_start_time',
               'field_location' => 'nullable|string|max:200',
               'coach_id' => 'nullable|exists:users,id'
           ];
       }
   }
   ```

#### Criterios de Aceptación:
- [ ] Modelo Category creado y funcional
- [ ] CRUD de categorías implementado
- [ ] Validaciones de edad y capacidad funcionando
- [ ] Relaciones con entrenador establecidas

---

### 3. Sistema de Gestión de Jugadores

**Responsable:** Backend Developer  
**Estimación:** 3 días  
**Prioridad:** Alta

#### Subtareas:

1. **Crear modelo Player:**
   ```php
   // Migration: create_players_table
   Schema::create('players', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('school_id');
       $table->unsignedBigInteger('category_id');
       $table->string('first_name');
       $table->string('last_name');
       $table->date('birth_date');
       $table->enum('gender', ['male', 'female']);
       $table->string('document_type')->default('CC'); // CC, TI, CE, etc.
       $table->string('document_number')->unique();
       $table->string('phone')->nullable();
       $table->string('email')->nullable();
       $table->text('address')->nullable();
       $table->string('emergency_contact_name');
       $table->string('emergency_contact_phone');
       $table->string('emergency_contact_relationship');
       $table->text('medical_conditions')->nullable();
       $table->text('allergies')->nullable();
       $table->string('blood_type')->nullable();
       $table->string('eps')->nullable(); // EPS/Seguro médico
       $table->enum('position', ['goalkeeper', 'defender', 'midfielder', 'forward', 'versatile'])->nullable();
       $table->integer('jersey_number')->nullable();
       $table->date('enrollment_date');
       $table->enum('status', ['active', 'inactive', 'suspended', 'graduated'])->default('active');
       $table->text('notes')->nullable();
       $table->string('photo_path')->nullable();
       $table->timestamps();
       
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('category_id')->references('id')->on('categories');
       $table->unique(['school_id', 'jersey_number', 'category_id']);
       $table->index(['school_id', 'status']);
       $table->index(['category_id', 'status']);
   });
   ```

2. **Implementar modelo Player:**
   ```php
   class Player extends Model
   {
       protected $fillable = [
           'school_id', 'category_id', 'first_name', 'last_name', 'birth_date',
           'gender', 'document_type', 'document_number', 'phone', 'email', 'address',
           'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship',
           'medical_conditions', 'allergies', 'blood_type', 'eps', 'position',
           'jersey_number', 'enrollment_date', 'status', 'notes', 'photo_path'
       ];
       
       protected $casts = [
           'birth_date' => 'date',
           'enrollment_date' => 'date'
       ];
       
       // Relaciones
       public function school() {
           return $this->belongsTo(School::class);
       }
       
       public function category() {
           return $this->belongsTo(Category::class);
       }
       
       public function attendances() {
           return $this->hasMany(Attendance::class);
       }
       
       public function evaluations() {
           return $this->hasMany(PlayerEvaluation::class);
       }
       
       // Accessors
       public function getFullNameAttribute() {
           return $this->first_name . ' ' . $this->last_name;
       }
       
       public function getAgeAttribute() {
           return $this->birth_date->age;
       }
       
       public function getPhotoUrlAttribute() {
           return $this->photo_path ? Storage::url($this->photo_path) : null;
       }
       
       // Scopes
       public function scopeActive($query) {
           return $query->where('status', 'active');
       }
       
       public function scopeByCategory($query, $categoryId) {
           return $query->where('category_id', $categoryId);
       }
       
       public function scopeByPosition($query, $position) {
           return $query->where('position', $position);
       }
       
       // Métodos auxiliares
       public function canPlayInCategory(Category $category) {
           return $category->canAcceptPlayer($this->birth_date) && 
                  ($category->gender === 'mixed' || $category->gender === $this->gender);
       }
       
       public function getAttendanceRate($period = 30) {
           $totalTrainings = Training::where('category_id', $this->category_id)
               ->where('date', '>=', now()->subDays($period))
               ->count();
               
           if ($totalTrainings === 0) return 0;
           
           $attendedTrainings = $this->attendances()
               ->where('date', '>=', now()->subDays($period))
               ->where('status', 'present')
               ->count();
               
           return round(($attendedTrainings / $totalTrainings) * 100, 2);
       }
   }
   ```

3. **Crear PlayerController:**
   ```php
   class PlayerController extends Controller
   {
       public function index(Request $request) {
           $players = Player::with(['category', 'school'])
               ->where('school_id', $request->user()->school_id)
               ->when($request->category_id, fn($q, $cat) => $q->byCategory($cat))
               ->when($request->status, fn($q, $status) => $q->where('status', $status))
               ->when($request->position, fn($q, $pos) => $q->byPosition($pos))
               ->when($request->search, function($q, $search) {
                   $q->where(function($query) use ($search) {
                       $query->where('first_name', 'like', "%{$search}%")
                             ->orWhere('last_name', 'like', "%{$search}%")
                             ->orWhere('document_number', 'like', "%{$search}%");
                   });
               })
               ->orderBy('last_name')
               ->paginate(20);
               
           return PlayerResource::collection($players);
       }
       
       public function store(StorePlayerRequest $request) {
           DB::beginTransaction();
           try {
               $playerData = $request->validated();
               $playerData['school_id'] = $request->user()->school_id;
               
               // Asignar número de camiseta automáticamente si no se proporciona
               if (!$playerData['jersey_number']) {
                   $playerData['jersey_number'] = $this->getNextJerseyNumber(
                       $playerData['category_id']
                   );
               }
               
               $player = Player::create($playerData);
               
               // Procesar foto si se proporciona
               if ($request->hasFile('photo')) {
                   $photoPath = $request->file('photo')->store(
                       'players/photos', 'public'
                   );
                   $player->update(['photo_path' => $photoPath]);
               }
               
               DB::commit();
               
               return new PlayerResource($player->load(['category', 'school']));
           } catch (Exception $e) {
               DB::rollback();
               throw $e;
           }
       }
       
       public function show(Player $player) {
           $this->authorize('view', $player);
           
           return new PlayerResource($player->load([
               'category', 'school', 'attendances' => fn($q) => $q->recent(10)
           ]));
       }
       
       public function update(UpdatePlayerRequest $request, Player $player) {
           $this->authorize('update', $player);
           
           DB::beginTransaction();
           try {
               $player->update($request->validated());
               
               // Actualizar foto si se proporciona
               if ($request->hasFile('photo')) {
                   // Eliminar foto anterior
                   if ($player->photo_path) {
                       Storage::disk('public')->delete($player->photo_path);
                   }
                   
                   $photoPath = $request->file('photo')->store(
                       'players/photos', 'public'
                   );
                   $player->update(['photo_path' => $photoPath]);
               }
               
               DB::commit();
               
               return new PlayerResource($player->load(['category', 'school']));
           } catch (Exception $e) {
               DB::rollback();
               throw $e;
           }
       }
       
       public function destroy(Player $player) {
           $this->authorize('delete', $player);
           
           // Verificar si tiene asistencias registradas
           if ($player->attendances()->exists()) {
               return response()->json([
                   'message' => 'No se puede eliminar un jugador con asistencias registradas'
               ], 422);
           }
           
           // Eliminar foto
           if ($player->photo_path) {
               Storage::disk('public')->delete($player->photo_path);
           }
           
           $player->delete();
           
           return response()->json(['message' => 'Jugador eliminado exitosamente']);
       }
       
       public function getStats(Player $player) {
           $this->authorize('view', $player);
           
           return response()->json([
               'attendance_rate' => $player->getAttendanceRate(),
               'total_trainings' => $player->attendances()->count(),
               'present_count' => $player->attendances()->where('status', 'present')->count(),
               'absent_count' => $player->attendances()->where('status', 'absent')->count(),
               'late_count' => $player->attendances()->where('status', 'late')->count(),
               'enrollment_days' => $player->enrollment_date->diffInDays(now())
           ]);
       }
       
       private function getNextJerseyNumber($categoryId) {
           $lastNumber = Player::where('category_id', $categoryId)
               ->max('jersey_number') ?? 0;
           return $lastNumber + 1;
       }
   }
   ```

4. **Crear validaciones para jugadores:**
   ```php
   class StorePlayerRequest extends FormRequest
   {
       public function rules() {
           return [
               'category_id' => 'required|exists:categories,id',
               'first_name' => 'required|string|max:100',
               'last_name' => 'required|string|max:100',
               'birth_date' => 'required|date|before:today',
               'gender' => 'required|in:male,female',
               'document_type' => 'required|string|max:10',
               'document_number' => 'required|string|max:20|unique:players,document_number',
               'phone' => 'nullable|string|max:20',
               'email' => 'nullable|email|max:100',
               'address' => 'nullable|string|max:300',
               'emergency_contact_name' => 'required|string|max:100',
               'emergency_contact_phone' => 'required|string|max:20',
               'emergency_contact_relationship' => 'required|string|max:50',
               'medical_conditions' => 'nullable|string|max:1000',
               'allergies' => 'nullable|string|max:500',
               'blood_type' => 'nullable|string|max:5',
               'eps' => 'nullable|string|max:100',
               'position' => 'nullable|in:goalkeeper,defender,midfielder,forward,versatile',
               'jersey_number' => 'nullable|integer|min:1|max:99',
               'enrollment_date' => 'required|date|before_or_equal:today',
               'notes' => 'nullable|string|max:1000',
               'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
           ];
       }
       
       public function withValidator($validator) {
           $validator->after(function ($validator) {
               // Validar que el jugador pueda estar en la categoría seleccionada
               if ($this->category_id && $this->birth_date) {
                   $category = Category::find($this->category_id);
                   if ($category && !$category->canAcceptPlayer($this->birth_date)) {
                       $validator->errors()->add('birth_date', 
                           'La edad del jugador no es compatible con la categoría seleccionada.');
                   }
               }
               
               // Validar número de camiseta único por categoría
               if ($this->jersey_number && $this->category_id) {
                   $exists = Player::where('category_id', $this->category_id)
                       ->where('jersey_number', $this->jersey_number)
                       ->exists();
                   if ($exists) {
                       $validator->errors()->add('jersey_number', 
                           'Este número de camiseta ya está en uso en la categoría.');
                   }
               }
           });
       }
   }
   ```

#### Criterios de Aceptación:
- [ ] Modelo Player creado con todas las validaciones
- [ ] CRUD de jugadores funcionando
- [ ] Validaciones de categoría y edad operativas
- [ ] Sistema de fotos implementado
- [ ] Estadísticas básicas funcionando

---

### 4. Sistema de Entrenamientos

**Responsable:** Backend Developer  
**Estimación:** 3 días  
**Prioridad:** Alta

#### Subtareas:

1. **Crear modelo Training:**
   ```php
   // Migration: create_trainings_table
   Schema::create('trainings', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('school_id');
       $table->unsignedBigInteger('category_id');
       $table->date('date');
       $table->time('start_time');
       $table->time('end_time');
       $table->string('location');
       $table->enum('type', ['training', 'match', 'friendly', 'tournament']);
       $table->text('objectives')->nullable();
       $table->text('activities')->nullable();
       $table->text('observations')->nullable();
       $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
       $table->unsignedBigInteger('coach_id');
       $table->json('weather_conditions')->nullable();
       $table->integer('duration_minutes')->nullable();
       $table->timestamps();
       
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('category_id')->references('id')->on('categories');
       $table->foreign('coach_id')->references('id')->on('users');
       $table->index(['school_id', 'date']);
       $table->index(['category_id', 'date']);
       $table->index(['coach_id', 'date']);
   });
   ```

2. **Implementar modelo Training:**
   ```php
   class Training extends Model
   {
       protected $fillable = [
           'school_id', 'category_id', 'date', 'start_time', 'end_time',
           'location', 'type', 'objectives', 'activities', 'observations',
           'status', 'coach_id', 'weather_conditions', 'duration_minutes'
       ];
       
       protected $casts = [
           'date' => 'date',
           'start_time' => 'datetime:H:i',
           'end_time' => 'datetime:H:i',
           'weather_conditions' => 'array'
       ];
       
       // Relaciones
       public function school() {
           return $this->belongsTo(School::class);
       }
       
       public function category() {
           return $this->belongsTo(Category::class);
       }
       
       public function coach() {
           return $this->belongsTo(User::class, 'coach_id');
       }
       
       public function attendances() {
           return $this->hasMany(Attendance::class);
       }
       
       // Scopes
       public function scopeUpcoming($query) {
           return $query->where('date', '>=', now()->toDateString())
                       ->where('status', 'scheduled');
       }
       
       public function scopeCompleted($query) {
           return $query->where('status', 'completed');
       }
       
       public function scopeByCategory($query, $categoryId) {
           return $query->where('category_id', $categoryId);
       }
       
       public function scopeByCoach($query, $coachId) {
           return $query->where('coach_id', $coachId);
       }
       
       public function scopeRecent($query, $limit = 10) {
           return $query->orderBy('date', 'desc')
                       ->orderBy('start_time', 'desc')
                       ->limit($limit);
       }
       
       // Métodos auxiliares
       public function getAttendanceStats() {
           $total = $this->category->players()->active()->count();
           $present = $this->attendances()->where('status', 'present')->count();
           $absent = $this->attendances()->where('status', 'absent')->count();
           $late = $this->attendances()->where('status', 'late')->count();
           
           return [
               'total_players' => $total,
               'present' => $present,
               'absent' => $absent,
               'late' => $late,
               'attendance_rate' => $total > 0 ? round(($present / $total) * 100, 2) : 0
           ];
       }
       
       public function canTakeAttendance() {
           return $this->status === 'in_progress' || 
                  ($this->status === 'scheduled' && $this->date->isToday());
       }
       
       public function isToday() {
           return $this->date->isToday();
       }
   }
   ```

3. **Crear TrainingController:**
   ```php
   class TrainingController extends Controller
   {
       public function index(Request $request) {
           $trainings = Training::with(['category', 'coach', 'attendances'])
               ->where('school_id', $request->user()->school_id)
               ->when($request->category_id, fn($q, $cat) => $q->byCategory($cat))
               ->when($request->coach_id, fn($q, $coach) => $q->byCoach($coach))
               ->when($request->status, fn($q, $status) => $q->where('status', $status))
               ->when($request->type, fn($q, $type) => $q->where('type', $type))
               ->when($request->date_from, fn($q, $date) => $q->where('date', '>=', $date))
               ->when($request->date_to, fn($q, $date) => $q->where('date', '<=', $date))
               ->orderBy('date', 'desc')
               ->orderBy('start_time', 'desc')
               ->paginate(20);
               
           return TrainingResource::collection($trainings);
       }
       
       public function store(StoreTrainingRequest $request) {
           $training = Training::create([
               'school_id' => $request->user()->school_id,
               ...$request->validated()
           ]);
           
           // Crear asistencias automáticamente para todos los jugadores de la categoría
           $this->createAttendanceRecords($training);
           
           return new TrainingResource($training->load(['category', 'coach', 'attendances']));
       }
       
       public function show(Training $training) {
           $this->authorize('view', $training);
           
           return new TrainingResource($training->load([
               'category', 'coach', 'attendances.player'
           ]));
       }
       
       public function update(UpdateTrainingRequest $request, Training $training) {
           $this->authorize('update', $training);
           
           $training->update($request->validated());
           
           return new TrainingResource($training->load(['category', 'coach', 'attendances']));
       }
       
       public function destroy(Training $training) {
           $this->authorize('delete', $training);
           
           if ($training->status === 'completed') {
               return response()->json([
                   'message' => 'No se puede eliminar un entrenamiento completado'
               ], 422);
           }
           
           $training->delete();
           
           return response()->json(['message' => 'Entrenamiento eliminado exitosamente']);
       }
       
       public function startTraining(Training $training) {
           $this->authorize('update', $training);
           
           if ($training->status !== 'scheduled') {
               return response()->json([
                   'message' => 'Solo se pueden iniciar entrenamientos programados'
               ], 422);
           }
           
           $training->update(['status' => 'in_progress']);
           
           return new TrainingResource($training);
       }
       
       public function completeTraining(CompleteTrainingRequest $request, Training $training) {
           $this->authorize('update', $training);
           
           if ($training->status !== 'in_progress') {
               return response()->json([
                   'message' => 'Solo se pueden completar entrenamientos en progreso'
               ], 422);
           }
           
           $training->update([
               'status' => 'completed',
               'observations' => $request->observations,
               'duration_minutes' => $request->duration_minutes
           ]);
           
           return new TrainingResource($training);
       }
       
       public function getUpcoming(Request $request) {
           $trainings = Training::with(['category', 'coach'])
               ->where('school_id', $request->user()->school_id)
               ->upcoming()
               ->when($request->category_id, fn($q, $cat) => $q->byCategory($cat))
               ->orderBy('date')
               ->orderBy('start_time')
               ->limit(10)
               ->get();
               
           return TrainingResource::collection($trainings);
       }
       
       private function createAttendanceRecords(Training $training) {
           $players = $training->category->players()->active()->get();
           
           foreach ($players as $player) {
               Attendance::create([
                   'school_id' => $training->school_id,
                   'training_id' => $training->id,
                   'player_id' => $player->id,
                   'date' => $training->date,
                   'status' => 'pending'
               ]);
           }
       }
   }
   ```

4. **Crear validaciones para entrenamientos:**
   ```php
   class StoreTrainingRequest extends FormRequest
   {
       public function rules() {
           return [
               'category_id' => 'required|exists:categories,id',
               'date' => 'required|date|after_or_equal:today',
               'start_time' => 'required|date_format:H:i',
               'end_time' => 'required|date_format:H:i|after:start_time',
               'location' => 'required|string|max:200',
               'type' => 'required|in:training,match,friendly,tournament',
               'objectives' => 'nullable|string|max:1000',
               'activities' => 'nullable|string|max:2000',
               'coach_id' => 'required|exists:users,id',
               'weather_conditions' => 'nullable|array',
               'weather_conditions.temperature' => 'nullable|numeric',
               'weather_conditions.humidity' => 'nullable|numeric',
               'weather_conditions.conditions' => 'nullable|string|max:100'
           ];
       }
       
       public function withValidator($validator) {
           $validator->after(function ($validator) {
               // Validar que no haya conflictos de horario
               if ($this->category_id && $this->date && $this->start_time && $this->end_time) {
                   $conflict = Training::where('category_id', $this->category_id)
                       ->where('date', $this->date)
                       ->where('status', '!=', 'cancelled')
                       ->where(function($q) {
                           $q->whereBetween('start_time', [$this->start_time, $this->end_time])
                             ->orWhereBetween('end_time', [$this->start_time, $this->end_time])
                             ->orWhere(function($q2) {
                                 $q2->where('start_time', '<=', $this->start_time)
                                    ->where('end_time', '>=', $this->end_time);
                             });
                       })
                       ->exists();
                       
                   if ($conflict) {
                       $validator->errors()->add('start_time', 
                           'Ya existe un entrenamiento programado en este horario.');
                   }
               }
           });
       }
   }
   ```

#### Criterios de Aceptación:
- [ ] Modelo Training creado y funcional
- [ ] CRUD de entrenamientos implementado
- [ ] Validaciones de conflictos de horario funcionando
- [ ] Estados de entrenamiento manejados correctamente
- [ ] Creación automática de asistencias implementada

---

### 5. Sistema de Control de Asistencia

**Responsable:** Backend Developer  
**Estimación:** 2 días  
**Prioridad:** Alta

#### Subtareas:

1. **Crear modelo Attendance:**
   ```php
   // Migration: create_attendances_table
   Schema::create('attendances', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('school_id');
       $table->unsignedBigInteger('training_id');
       $table->unsignedBigInteger('player_id');
       $table->date('date');
       $table->enum('status', ['pending', 'present', 'absent', 'late', 'excused'])->default('pending');
       $table->time('arrival_time')->nullable();
       $table->text('notes')->nullable();
       $table->unsignedBigInteger('recorded_by')->nullable();
       $table->timestamp('recorded_at')->nullable();
       $table->timestamps();
       
       $table->foreign('school_id')->references('id')->on('schools');
       $table->foreign('training_id')->references('id')->on('trainings');
       $table->foreign('player_id')->references('id')->on('players');
       $table->foreign('recorded_by')->references('id')->on('users');
       $table->unique(['training_id', 'player_id']);
       $table->index(['school_id', 'date']);
       $table->index(['player_id', 'date']);
   });
   ```

2. **Implementar modelo Attendance:**
   ```php
   class Attendance extends Model
   {
       protected $fillable = [
           'school_id', 'training_id', 'player_id', 'date', 'status',
           'arrival_time', 'notes', 'recorded_by', 'recorded_at'
       ];
       
       protected $casts = [
           'date' => 'date',
           'arrival_time' => 'datetime:H:i',
           'recorded_at' => 'datetime'
       ];
       
       // Relaciones
       public function school() {
           return $this->belongsTo(School::class);
       }
       
       public function training() {
           return $this->belongsTo(Training::class);
       }
       
       public function player() {
           return $this->belongsTo(Player::class);
       }
       
       public function recordedBy() {
           return $this->belongsTo(User::class, 'recorded_by');
       }
       
       // Scopes
       public function scopePresent($query) {
           return $query->where('status', 'present');
       }
       
       public function scopeAbsent($query) {
           return $query->where('status', 'absent');
       }
       
       public function scopeLate($query) {
           return $query->where('status', 'late');
       }
       
       public function scopeByPlayer($query, $playerId) {
           return $query->where('player_id', $playerId);
       }
       
       public function scopeByTraining($query, $trainingId) {
           return $query->where('training_id', $trainingId);
       }
       
       public function scopeRecent($query, $limit = 10) {
           return $query->orderBy('date', 'desc')->limit($limit);
       }
       
       // Métodos auxiliares
       public function isLate() {
           if (!$this->arrival_time || !$this->training) {
               return false;
           }
           
           return $this->arrival_time->gt($this->training->start_time);
       }
       
       public function getLateDuration() {
           if (!$this->isLate()) {
               return 0;
           }
           
           return $this->training->start_time->diffInMinutes($this->arrival_time);
       }
   }
   ```

3. **Crear AttendanceController:**
   ```php
   class AttendanceController extends Controller
   {
       public function index(Request $request) {
           $attendances = Attendance::with(['training.category', 'player'])
               ->where('school_id', $request->user()->school_id)
               ->when($request->training_id, fn($q, $training) => $q->byTraining($training))
               ->when($request->player_id, fn($q, $player) => $q->byPlayer($player))
               ->when($request->status, fn($q, $status) => $q->where('status', $status))
               ->when($request->date_from, fn($q, $date) => $q->where('date', '>=', $date))
               ->when($request->date_to, fn($q, $date) => $q->where('date', '<=', $date))
               ->orderBy('date', 'desc')
               ->paginate(50);
               
           return AttendanceResource::collection($attendances);
       }
       
       public function getByTraining(Training $training) {
           $this->authorize('view', $training);
           
           $attendances = $training->attendances()
               ->with(['player'])
               ->orderBy('player_id')
               ->get();
               
           return AttendanceResource::collection($attendances);
       }
       
       public function updateAttendance(UpdateAttendanceRequest $request, Attendance $attendance) {
           $this->authorize('update', $attendance);
           
           $data = $request->validated();
           $data['recorded_by'] = $request->user()->id;
           $data['recorded_at'] = now();
           
           // Si se marca como presente y se proporciona hora de llegada
           if ($data['status'] === 'present' && $request->arrival_time) {
               $data['arrival_time'] = $request->arrival_time;
               
               // Determinar si llegó tarde
               $trainingStart = $attendance->training->start_time;
               $arrivalTime = Carbon::parse($request->arrival_time);
               
               if ($arrivalTime->gt($trainingStart)) {
                   $data['status'] = 'late';
               }
           }
           
           $attendance->update($data);
           
           return new AttendanceResource($attendance->load(['training', 'player']));
       }
       
       public function bulkUpdateAttendance(BulkUpdateAttendanceRequest $request) {
           DB::beginTransaction();
           try {
               $attendances = [];
               
               foreach ($request->attendances as $attendanceData) {
                   $attendance = Attendance::findOrFail($attendanceData['id']);
                   $this->authorize('update', $attendance);
                   
                   $updateData = [
                       'status' => $attendanceData['status'],
                       'notes' => $attendanceData['notes'] ?? null,
                       'recorded_by' => $request->user()->id,
                       'recorded_at' => now()
                   ];
                   
                   if (isset($attendanceData['arrival_time'])) {
                       $updateData['arrival_time'] = $attendanceData['arrival_time'];
                   }
                   
                   $attendance->update($updateData);
                   $attendances[] = $attendance;
               }
               
               DB::commit();
               
               return AttendanceResource::collection(collect($attendances));
           } catch (Exception $e) {
               DB::rollback();
               throw $e;
           }
       }
       
       public function getPlayerAttendanceStats(Player $player, Request $request) {
           $this->authorize('view', $player);
           
           $period = $request->period ?? 30; // días
           $dateFrom = now()->subDays($period);
           
           $stats = [
               'total_trainings' => $player->attendances()
                   ->where('date', '>=', $dateFrom)
                   ->count(),
               'present' => $player->attendances()
                   ->where('date', '>=', $dateFrom)
                   ->present()
                   ->count(),
               'absent' => $player->attendances()
                   ->where('date', '>=', $dateFrom)
                   ->absent()
                   ->count(),
               'late' => $player->attendances()
                   ->where('date', '>=', $dateFrom)
                   ->late()
                   ->count(),
               'excused' => $player->attendances()
                   ->where('date', '>=', $dateFrom)
                   ->where('status', 'excused')
                   ->count()
           ];
           
           $stats['attendance_rate'] = $stats['total_trainings'] > 0 
               ? round((($stats['present'] + $stats['late']) / $stats['total_trainings']) * 100, 2)
               : 0;
               
           return response()->json($stats);
       }
       
       public function getCategoryAttendanceReport(Category $category, Request $request) {
           $this->authorize('view', $category);
           
           $dateFrom = $request->date_from ?? now()->subMonth()->toDateString();
           $dateTo = $request->date_to ?? now()->toDateString();
           
           $report = $category->players()->active()->get()->map(function($player) use ($dateFrom, $dateTo) {
               $attendances = $player->attendances()
                   ->whereBetween('date', [$dateFrom, $dateTo])
                   ->get();
                   
               return [
                   'player' => new PlayerResource($player),
                   'total_trainings' => $attendances->count(),
                   'present' => $attendances->where('status', 'present')->count(),
                   'absent' => $attendances->where('status', 'absent')->count(),
                   'late' => $attendances->where('status', 'late')->count(),
                   'excused' => $attendances->where('status', 'excused')->count(),
                   'attendance_rate' => $attendances->count() > 0 
                       ? round((($attendances->where('status', 'present')->count() + $attendances->where('status', 'late')->count()) / $attendances->count()) * 100, 2)
                       : 0
               ];
           });
           
           return response()->json($report);
       }
   }
   ```

4. **Crear validaciones para asistencia:**
   ```php
   class UpdateAttendanceRequest extends FormRequest
   {
       public function rules() {
           return [
               'status' => 'required|in:present,absent,late,excused',
               'arrival_time' => 'nullable|date_format:H:i',
               'notes' => 'nullable|string|max:500'
           ];
       }
   }
   
   class BulkUpdateAttendanceRequest extends FormRequest
   {
       public function rules() {
           return [
               'attendances' => 'required|array|min:1',
               'attendances.*.id' => 'required|exists:attendances,id',
               'attendances.*.status' => 'required|in:present,absent,late,excused',
               'attendances.*.arrival_time' => 'nullable|date_format:H:i',
               'attendances.*.notes' => 'nullable|string|max:500'
           ];
       }
   }
   ```

#### Criterios de Aceptación:
- [ ] Modelo Attendance creado y funcional
- [ ] Control de asistencia individual y masivo funcionando
- [ ] Estadísticas de asistencia por jugador implementadas
- [ ] Reportes de asistencia por categoría operativos
- [ ] Detección automática de llegadas tarde funcionando

---

### 6. Frontend - Módulo Deportivo Básico

**Responsable:** Frontend Developer  
**Estimación:** 4 días  
**Prioridad:** Alta

#### Subtareas:

1. **Crear componentes para categorías:**
   ```typescript
   // CategoriesList - Lista de categorías
   // CategoryForm - Formulario de categoría
   // CategoryDetail - Detalle de categoría
   // CategoryCard - Tarjeta de categoría
   ```

2. **Crear componentes para jugadores:**
   ```typescript
   // PlayersList - Lista de jugadores
   // PlayerForm - Formulario de jugador
   // PlayerDetail - Detalle de jugador
   // PlayerCard - Tarjeta de jugador
   // PlayerStats - Estadísticas de jugador
   // PlayerPhoto - Componente de foto
   ```

3. **Crear componentes para entrenamientos:**
   ```typescript
   // TrainingsList - Lista de entrenamientos
   // TrainingForm - Formulario de entrenamiento
   // TrainingDetail - Detalle de entrenamiento
   // TrainingCalendar - Calendario de entrenamientos
   // UpcomingTrainings - Próximos entrenamientos
   ```

4. **Crear componentes para asistencia:**
   ```typescript
   // AttendanceList - Lista de asistencias
   // AttendanceForm - Formulario de asistencia
   // AttendanceTracker - Control de asistencia
   // AttendanceStats - Estadísticas de asistencia
   // AttendanceReport - Reporte de asistencia
   ```

5. **Implementar gestión de estado:**
   ```typescript
   interface SportsState {
     categories: Category[];
     players: Player[];
     trainings: Training[];
     attendances: Attendance[];
     selectedCategory: Category | null;
     selectedPlayer: Player | null;
     selectedTraining: Training | null;
     filters: SportsFilters;
     isLoading: boolean;
     error: string | null;
   }
   ```

6. **Crear servicios de API:**
   ```typescript
   class SportsService {
     // Categorías
     async getCategories(filters?: CategoryFilters): Promise<PaginatedResponse<Category>>
     async createCategory(data: CreateCategoryData): Promise<Category>
     async updateCategory(id: string, data: UpdateCategoryData): Promise<Category>
     async deleteCategory(id: string): Promise<void>
     
     // Jugadores
     async getPlayers(filters?: PlayerFilters): Promise<PaginatedResponse<Player>>
     async createPlayer(data: CreatePlayerData): Promise<Player>
     async updatePlayer(id: string, data: UpdatePlayerData): Promise<Player>
     async deletePlayer(id: string): Promise<void>
     async getPlayerStats(id: string): Promise<PlayerStats>
     
     // Entrenamientos
     async getTrainings(filters?: TrainingFilters): Promise<PaginatedResponse<Training>>
     async createTraining(data: CreateTrainingData): Promise<Training>
     async updateTraining(id: string, data: UpdateTrainingData): Promise<Training>
     async deleteTraining(id: string): Promise<void>
     async startTraining(id: string): Promise<Training>
     async completeTraining(id: string, data: CompleteTrainingData): Promise<Training>
     
     // Asistencia
     async getAttendances(filters?: AttendanceFilters): Promise<PaginatedResponse<Attendance>>
     async updateAttendance(id: string, data: UpdateAttendanceData): Promise<Attendance>
     async bulkUpdateAttendance(data: BulkAttendanceData): Promise<Attendance[]>
     async getPlayerAttendanceStats(playerId: string, period?: number): Promise<AttendanceStats>
   }
   ```

7. **Crear dashboard deportivo:**
   ```typescript
   // Métricas principales:
   // - Total de categorías activas
   // - Total de jugadores activos
   // - Entrenamientos de hoy
   // - Próximos entrenamientos
   // - Estadísticas de asistencia general
   // - Jugadores con mejor asistencia
   // - Categorías con más actividad
   ```

#### Criterios de Aceptación:
- [ ] Componentes de categorías funcionando
- [ ] Gestión de jugadores implementada
- [ ] Programación de entrenamientos operativa
- [ ] Control de asistencia funcional
- [ ] Dashboard deportivo implementado
- [ ] Navegación entre módulos fluida

---

## API Endpoints Implementados

### Categories
```
GET    /api/v1/sports/categories
POST   /api/v1/sports/categories
GET    /api/v1/sports/categories/{id}
PUT    /api/v1/sports/categories/{id}
DELETE /api/v1/sports/categories/{id}
```

### Players
```
GET    /api/v1/sports/players
POST   /api/v1/sports/players
GET    /api/v1/sports/players/{id}
PUT    /api/v1/sports/players/{id}
DELETE /api/v1/sports/players/{id}
GET    /api/v1/sports/players/{id}/stats
```

### Trainings
```
GET    /api/v1/sports/trainings
POST   /api/v1/sports/trainings
GET    /api/v1/sports/trainings/{id}
PUT    /api/v1/sports/trainings/{id}
DELETE /api/v1/sports/trainings/{id}
POST   /api/v1/sports/trainings/{id}/start
POST   /api/v1/sports/trainings/{id}/complete
GET    /api/v1/sports/trainings/upcoming
```

### Attendance
```
GET    /api/v1/sports/attendances
GET    /api/v1/sports/attendances/training/{trainingId}
PUT    /api/v1/sports/attendances/{id}
POST   /api/v1/sports/attendances/bulk-update
GET    /api/v1/sports/attendances/player/{playerId}/stats
GET    /api/v1/sports/attendances/category/{categoryId}/report
```

## Definición de Terminado (DoD)

### Criterios Técnicos:
- [ ] Sports Service configurado y funcionando
- [ ] Gestión de categorías implementada
- [ ] Sistema de jugadores operativo
- [ ] Programación de entrenamientos funcional
- [ ] Control de asistencia implementado
- [ ] Frontend deportivo funcionando
- [ ] API endpoints documentados

### Criterios de Calidad:
- [ ] Tests unitarios > 85% cobertura
- [ ] Tests de integración pasando
- [ ] Performance validada (< 500ms consultas)
- [ ] Validaciones de negocio funcionando
- [ ] UX validada con entrenadores

### Criterios de Negocio:
- [ ] Categorías se crean con validaciones de edad
- [ ] Jugadores se asignan correctamente a categorías
- [ ] Entrenamientos se programan sin conflictos
- [ ] Asistencia se registra automáticamente
- [ ] Estadísticas son precisas y útiles

## Riesgos Identificados

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|---------|------------|
| Complejidad validaciones edad | Media | Alto | Casos de prueba exhaustivos, validación UX |
| Performance con muchos jugadores | Alta | Medio | Paginación, índices BD, cache |
| Conflictos horarios entrenamientos | Media | Alto | Validaciones robustas, UX clara |
| Usabilidad control asistencia | Alta | Alto | Prototipado, testing con usuarios |

## Métricas de Éxito

- **Response time**: < 500ms para consultas deportivas
- **Data accuracy**: 100% precisión en asistencias
- **User adoption**: > 80% entrenadores usando el sistema
- **Error rate**: < 1% en operaciones deportivas
- **User satisfaction**: > 4.0/5 en módulo deportivo

## Entregables

1. **Sports Service** - Microservicio configurado
2. **Gestión de Categorías** - CRUD completo
3. **Sistema de Jugadores** - Registro y gestión
4. **Programación de Entrenamientos** - Calendario y gestión
5. **Control de Asistencia** - Registro y estadísticas
6. **Frontend Deportivo** - Interfaz completa

## Configuración de Entorno

### Variables Sports Service
```env
APP_NAME="WL School Sports Service"
APP_ENV=local
APP_KEY=base64:generated_key
APP_DEBUG=true
APP_URL=http://sports-service:8000

DB_CONNECTION=mysql
DB_HOST=mysql-sports
DB_PORT=3306
DB_DATABASE=sports_service
DB_USERNAME=sports_user
DB_PASSWORD=sports_password

JWT_SECRET=generated_jwt_secret
JWT_TTL=1440

FILE_STORAGE_DISK=public
PLAYER_PHOTOS_PATH=players/photos

AUTH_SERVICE_URL=http://auth-service:8000
NOTIFICATION_SERVICE_URL=http://notification-service:8000
```

## Datos de Prueba

### Categorías de Ejemplo
```json
{
  "categories": [
    {
      "name": "Sub-8",
      "min_age": 6,
      "max_age": 8,
      "gender": "mixed",
      "max_players": 20,
      "training_days": ["monday", "wednesday", "friday"],
      "training_start_time": "16:00",
      "training_end_time": "17:00"
    },
    {
      "name": "Sub-12 Masculino",
      "min_age": 10,
      "max_age": 12,
      "gender": "male",
      "max_players": 25,
      "training_days": ["tuesday", "thursday", "saturday"],
      "training_start_time": "17:00",
      "training_end_time": "18:30"
    }
  ]
}
```

### Jugadores de Ejemplo
```json
{
  "players": [
    {
      "first_name": "Carlos",
      "last_name": "Rodríguez",
      "birth_date": "2015-03-15",
      "gender": "male",
      "document_type": "TI",
      "document_number": "1234567890",
      "emergency_contact_name": "María Rodríguez",
      "emergency_contact_phone": "3001234567",
      "emergency_contact_relationship": "Madre",
      "position": "midfielder",
      "jersey_number": 10
    }
  ]
}
```

## Preguntas para Retrospectiva

1. **¿Qué funcionó bien en este sprint?**
2. **¿Qué obstáculos encontramos?**
3. **¿Las validaciones de edad y categoría son suficientes?**
4. **¿El sistema de asistencia es intuitivo para los entrenadores?**
5. **¿Qué mejoras podemos hacer en el próximo sprint?**
6. **¿La performance es adecuada con el volumen de datos esperado?**
7. **¿Los reportes generados son útiles para la gestión deportiva?**