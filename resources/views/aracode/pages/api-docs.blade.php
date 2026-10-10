<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentación de APIs - Base Aracode</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #6366f1;
            --dark-bg: #1e293b;
            --light-bg: #f8fafc;
            --border-color: #e2e8f0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
        }
        
        .sidebar {
            min-height: 100vh;
            background: var(--dark-bg);
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            overflow-y: auto;
        }
        
        .sidebar .logo {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar .logo h4 {
            margin: 0;
            font-weight: 600;
        }
        
        .sidebar .module-item {
            padding: 12px 20px;
            cursor: pointer;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar .module-item:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .sidebar .module-item.active {
            background: rgba(79, 70, 229, 0.2);
            border-left-color: var(--primary-color);
        }
        
        .sidebar .module-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            margin-left: 280px;
            padding: 40px;
        }
        
        .api-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .api-card .api-header {
            background: var(--dark-bg);
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .api-card .api-header .method {
            padding: 5px 12px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .api-card .api-header .method.get {
            background: #10b981;
        }
        
        .api-card .api-header .method.post {
            background: #3b82f6;
        }
        
        .api-card .api-header .method.put {
            background: #f59e0b;
        }
        
        .api-card .api-header .method.delete {
            background: #ef4444;
        }
        
        .api-card .api-header .endpoint {
            font-family: monospace;
            font-size: 14px;
            color: #e2e8f0;
        }
        
        .api-card .api-body {
            padding: 20px;
        }
        
        .api-card .section-title {
            font-weight: 600;
            color: var(--dark-bg);
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--primary-color);
            display: inline-block;
        }
        
        .code-block {
            background: #1e293b;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            margin: 10px 0;
        }
        
        .code-block .key {
            color: #93c5fd;
        }
        
        .code-block .string {
            color: #86efac;
        }
        
        .code-block .number {
            color: #fbbf24;
        }
        
        .code-block .boolean {
            color: #f472b6;
        }
        
        .param-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        .param-table th {
            background: #f1f5f9;
            padding: 10px;
            text-align: left;
            font-weight: 600;
            border: 1px solid var(--border-color);
        }
        
        .param-table td {
            padding: 10px;
            border: 1px solid var(--border-color);
        }
        
        .param-table .required {
            color: #dc2626;
            font-weight: 600;
        }
        
        .param-table .optional {
            color: #6b7280;
            font-style: italic;
        }
        
        .response-example {
            margin-top: 15px;
        }
        
        .badge-auth {
            background: #fef3c7;
            color: #92400e;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .breadcrumb {
            background: transparent;
            padding: 0;
        }
        
        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <h4><i class="fas fa-book"></i> API Docs</h4>
                <small class="text-muted">Base Aracode</small>
            </div>
            <div class="py-3">
                <div class="px-3 pb-2 text-muted text-uppercase small fw-bold">Módulos</div>
                
                <div class="module-item active" onclick="selectModule('academic')">
                    <i class="fas fa-graduation-cap"></i> Académico
                </div>
                
                <div class="module-item" onclick="selectModule('sales')">
                    <i class="fas fa-shopping-cart"></i> Ventas
                </div>
                
                <div class="module-item" onclick="selectModule('inventory')">
                    <i class="fas fa-box"></i> Inventario
                </div>
                
                <div class="module-item" onclick="selectModule('accounting')">
                    <i class="fas fa-calculator"></i> Contabilidad
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content" id="mainContent">
            <!-- Academic Module -->
            <div id="academic" class="module-content">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Inicio</a></li>
                        <li class="breadcrumb-item active">Académico</li>
                    </ol>
                </nav>
                
                <h2 class="mb-4"><i class="fas fa-graduation-cap me-2"></i>Módulo Académico</h2>
                <p class="text-muted mb-4">Endpoints disponibles para la gestión de estudiantes y cursos.</p>
                
                <!-- API 1 -->
                <div class="api-card">
                    <div class="api-header">
                        <span class="method get">GET</span>
                        <span class="endpoint">/api/academic/student/{dni}</span>
                        <span class="badge-auth"><i class="fas fa-lock"></i> Bearer Token</span>
                    </div>
                    <div class="api-body">
                        <h6 class="section-title">Consultar estudiante por DNI</h6>
                        <p>Retorna los datos de un estudiante existente utilizando su número de DNI.</p>
                        
                        <h6 class="fw-bold mt-4">Cabeceras requeridas:</h6>
                        <table class="param-table">
                            <tr>
                                <th>Header</th>
                                <th>Valor</th>
                            </tr>
                            <tr>
                                <td>Authorization</td>
                                <td>Bearer {token}</td>
                            </tr>
                            <tr>
                                <td>Content-Type</td>
                                <td>application/json</td>
                            </tr>
                        </table>
                        
                        <h6 class="fw-bold mt-4">Parámetros de URL:</h6>
                        <table class="param-table">
                            <tr>
                                <th>Parámetro</th>
                                <th>Tipo</th>
                                <th>Descripción</th>
                                <th>Requerido</th>
                            </tr>
                            <tr>
                                <td>dni</td>
                                <td>string</td>
                                <td>Número de DNI del estudiante (ej: 12345678)</td>
                                <td><span class="required">Sí</span></td>
                            </tr>
                        </table>
                        
                        <h6 class="fw-bold mt-4">Respuesta exitosa (200):</h6>
                        <div class="code-block">
<pre>{
  <span class="key">"success"</span>: <span class="boolean">true</span>,
  <span class="key">"message"</span>: <span class="string">"Estudiante encontrado"</span>,
  <span class="key">"data"</span>: {
    <span class="key">"person_id"</span>: <span class="number">1</span>,
    <span class="key">"names"</span>: <span class="string">"Juan Carlos"</span>,
    <span class="key">"father_lastname"</span>: <span class="string">"Pérez"</span>,
    <span class="key">"mother_lastname"</span>: <span class="string">"García"</span>,
    <span class="key">"number"</span>: <span class="string">"12345678"</span>,
    <span class="key">"email"</span>: <span class="string">"juan@email.com"</span>,
    <span class="key">"gender"</span>: <span class="string">"M"</span>,
    <span class="key">"student_code"</span>: <span class="string">"12345678"</span>,
    <span class="key">"student_id"</span>: <span class="number">1</span>
  }
}</pre>
                        </div>
                        
                        <h6 class="fw-bold mt-4">Respuesta de error (404):</h6>
                        <div class="code-block">
<pre>{
  <span class="key">"success"</span>: <span class="boolean">false</span>,
  <span class="key">"message"</span>: <span class="string">"Estudiante no encontrado"</span>,
  <span class="key">"data"</span>: <span class="boolean">null</span>
}</pre>
                        </div>
                    </div>
                </div>
                
                <!-- API 2 -->
                <div class="api-card">
                    <div class="api-header">
                        <span class="method post">POST</span>
                        <span class="endpoint">/api/academic/students/register</span>
                        <span class="badge-auth"><i class="fas fa-lock"></i> Bearer Token</span>
                    </div>
                    <div class="api-body">
                        <h6 class="section-title">Registrar nuevo estudiante</h6>
                        <p>Registra un nuevo estudiante en el sistema. Crea el registro en las tablas people, aca_students y opcionalmente en aca_student_courses_interests.</p>
                        
                        <h6 class="fw-bold mt-4">Cabeceras requeridas:</h6>
                        <table class="param-table">
                            <tr>
                                <th>Header</th>
                                <th>Valor</th>
                            </tr>
                            <tr>
                                <td>Authorization</td>
                                <td>Bearer {token}</td>
                            </tr>
                            <tr>
                                <td>Content-Type</td>
                                <td>application/json</td>
                            </tr>
                        </table>
                        
                        <h6 class="fw-bold mt-4">Cuerpo de la solicitud (JSON):</h6>
                        <table class="param-table">
                            <tr>
                                <th>Parámetro</th>
                                <th>Tipo</th>
                                <th>Descripción</th>
                                <th>Requerido</th>
                            </tr>
                            <tr>
                                <td>names</td>
                                <td>string</td>
                                <td>Nombres del estudiante</td>
                                <td><span class="required">Sí</span></td>
                            </tr>
                            <tr>
                                <td>father_lastname</td>
                                <td>string</td>
                                <td>Apellido paterno</td>
                                <td><span class="required">Sí</span></td>
                            </tr>
                            <tr>
                                <td>mother_lastname</td>
                                <td>string</td>
                                <td>Apellido materno</td>
                                <td><span class="required">Sí</span></td>
                            </tr>
                            <tr>
                                <td>number</td>
                                <td>string</td>
                                <td>Número de DNI (8 dígitos)</td>
                                <td><span class="required">Sí</span></td>
                            </tr>
                            <tr>
                                <td>email</td>
                                <td>string</td>
                                <td>Correo electrónico</td>
                                <td><span class="optional">No</span></td>
                            </tr>
                            <tr>
                                <td>gender</td>
                                <td>string</td>
                                <td>Género (M = Masculino, F = Femenino)</td>
                                <td><span class="optional">No</span></td>
                            </tr>
                            <tr>
                                <td>course_interest_id</td>
                                <td>integer</td>
                                <td>ID del curso de interés</td>
                                <td><span class="optional">No</span></td>
                            </tr>
                        </table>
                        
                        <h6 class="fw-bold mt-4">Ejemplo de solicitud:</h6>
                        <div class="code-block">
<pre>{
  <span class="key">"names"</span>: <span class="string">"Juan Carlos"</span>,
  <span class="key">"father_lastname"</span>: <span class="string">"Pérez"</span>,
  <span class="key">"mother_lastname"</span>: <span class="string">"García"</span>,
  <span class="key">"number"</span>: <span class="string">"12345678"</span>,
  <span class="key">"email"</span>: <span class="string">"juan@email.com"</span>,
  <span class="key">"gender"</span>: <span class="string">"M"</span>,
  <span class="key">"course_interest_id"</span>: <span class="number">1</span>
}</pre>
                        </div>
                        
                        <h6 class="fw-bold mt-4">Respuesta exitosa (201):</h6>
                        <div class="code-block">
<pre>{
  <span class="key">"success"</span>: <span class="boolean">true</span>,
  <span class="key">"message"</span>: <span class="string">"Estudiante registrado correctamente"</span>,
  <span class="key">"data"</span>: {
    <span class="key">"student_id"</span>: <span class="number">1</span>,
    <span class="key">"person_id"</span>: <span class="number">1</span>,
    <span class="key">"student_code"</span>: <span class="string">"12345678"</span>
  }
}</pre>
                        </div>
                        
                        <h6 class="fw-bold mt-4">Respuesta de error (422):</h6>
                        <div class="code-block">
<pre>{
  <span class="key">"success"</span>: <span class="boolean">false</span>,
  <span class="key">"message"</span>: <span class="string">"The number has already been taken."</span>,
  <span class="key">"errors"</span>: {
    <span class="key">"number"</span>: [<span class="string">"The number has already been taken."</span>]
  }
}</pre>
                        </div>
                        
                        <h6 class="fw-bold mt-4">Respuesta de error (500):</h6>
                        <div class="code-block">
<pre>{
  <span class="key">"success"</span>: <span class="boolean">false</span>,
  <span class="key">"message"</span>: <span class="string">"Error al registrar estudiante: ..."</span>,
  <span class="key">"data"</span>: <span class="boolean">null</span>
}</pre>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Placeholder for other modules -->
            <div id="sales" class="module-content" style="display: none;">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Inicio</a></li>
                        <li class="breadcrumb-item active">Ventas</li>
                    </ol>
                </nav>
                <h2 class="mb-4"><i class="fas fa-shopping-cart me-2"></i>Módulo Ventas</h2>
                <p class="text-muted">Endpoints disponibles para la gestión de ventas.</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Próximamente más documentación para este módulo.
                </div>
            </div>
            
            <div id="inventory" class="module-content" style="display: none;">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Inicio</a></li>
                        <li class="breadcrumb-item active">Inventario</li>
                    </ol>
                </nav>
                <h2 class="mb-4"><i class="fas fa-box me-2"></i>Módulo Inventario</h2>
                <p class="text-muted">Endpoints disponibles para la gestión de inventarios.</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Próximamente más documentación para este módulo.
                </div>
            </div>
            
            <div id="accounting" class="module-content" style="display: none;">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Inicio</a></li>
                        <li class="breadcrumb-item active">Contabilidad</li>
                    </ol>
                </nav>
                <h2 class="mb-4"><i class="fas fa-calculator me-2"></i>Módulo Contabilidad</h2>
                <p class="text-muted">Endpoints disponibles para la gestión contable.</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Próximamente más documentación para este módulo.
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function selectModule(moduleId) {
            // Hide all modules
            document.querySelectorAll('.module-content').forEach(el => {
                el.style.display = 'none';
            });
            
            // Show selected module
            document.getElementById(moduleId).style.display = 'block';
            
            // Update active state in sidebar
            document.querySelectorAll('.module-item').forEach(el => {
                el.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
        }
    </script>
</body>
</html>