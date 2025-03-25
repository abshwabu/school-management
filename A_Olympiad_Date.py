t = int(input())
target = "01032025"

for _ in range(t):
    n = int(input())
    nums = input().split()
    
    found = [False] * 8 
    min_steps = 0
    
    
    for i in range(n):
        digit = nums[i]
        
        for j in range(8):
            if not found[j] and digit == target[j]:
                found[j] = True
                min_steps = i + 1
                break
                
        
        if all(found):
            break
            
    
    print(min_steps if all(found) else 0)

