# Exemple de MarkdownRenderer avec KaTeX et Prism

## Formules mathématiques

### Formules inline
Voici une formule inline : $E = mc^2$ dans le texte.

La vitesse de la lumière est $c = 3 \times 10^8$ m/s.

### Formules display
Équation quadratique :
$$x = \frac{-b \pm \sqrt{b^2 - 4ac}}{2a}$$

Intégrale de Gauss :
$$\int_{-\infty}^{\infty} e^{-x^2} dx = \sqrt{\pi}$$

Série de Taylor :
$$f(x) = \sum_{n=0}^{\infty} \frac{f^{(n)}(a)}{n!}(x-a)^n$$

## Coloration syntaxique

### JavaScript
```javascript
function fibonacci(n) {
    if (n <= 1) return n;
    return fibonacci(n - 1) + fibonacci(n - 2);
}

console.log(fibonacci(10)); // 55
```

### Python
```python
def quicksort(arr):
    if len(arr) <= 1:
        return arr
    pivot = arr[len(arr) // 2]
    left = [x for x in arr if x < pivot]
    middle = [x for x in arr if x == pivot]
    right = [x for x in arr if x > pivot]
    return quicksort(left) + middle + quicksort(right)

print(quicksort([3, 6, 8, 10, 1, 2, 1]))
```

### PHP
```php
<?php
class Calculator {
    public function add($a, $b) {
        return $a + $b;
    }
    
    public function multiply($a, $b) {
        return $a * $b;
    }
}

$calc = new Calculator();
echo $calc->add(5, 3); // 8
?>
```

### SQL
```sql
SELECT 
    students.name,
    AVG(exam_assignments.score) as average_score
FROM students
INNER JOIN exam_assignments ON students.id = exam_assignments.student_id
WHERE exam_assignments.status = 'graded'
GROUP BY students.id, students.name
HAVING AVG(exam_assignments.score) > 15
ORDER BY average_score DESC;
```

## Combinaison de texte, math et code

Pour calculer la moyenne d'un tableau en JavaScript :

```javascript
const scores = [18, 16, 14, 17, 19];
const average = scores.reduce((sum, score) => sum + score, 0) / scores.length;
```

La formule mathématique correspondante est :
$$\bar{x} = \frac{1}{n}\sum_{i=1}^{n} x_i$$

Où $\bar{x}$ est la moyenne, $n$ le nombre d'éléments, et $x_i$ chaque élément du tableau.